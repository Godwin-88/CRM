<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\AgentToolController;
use App\Models\User;
use App\Services\AssistantIntentService;
use App\Services\AssistantTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssistantChatController extends Controller
{
    public function __construct(
        protected AssistantTokenService $tokenService,
        protected AssistantIntentService $intentService,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $payload = $request->validate([
            'message' => 'nullable|string|max:4000',
            'session_id' => 'nullable|string|max:255',
            'context' => 'sometimes|array',
            'confirmed_actions' => 'sometimes|array',
            'confirmed_actions.*.tool' => 'required|string',
            'confirmed_actions.*.arguments' => 'sometimes|array',
        ]);

        if (blank($payload['message'] ?? null) && empty($payload['confirmed_actions'] ?? [])) {
            return response()->json([
                'response' => '',
                'session_id' => $request->input('session_id') ?: Str::ulid(),
                'intent' => null,
                'help_type' => null,
                'confidence' => null,
                'feature_refs' => [],
                'quick_replies' => [],
                'clarifying_options' => [],
                'navigation' => null,
                'tool_calls' => [],
                'requires_confirmation' => false,
            ]);
        }

        $mlServiceUrl = rtrim(config('services.ml_service.url', env('ML_SERVICE_URL', 'http://ml-agents:8000')), '/');
        $apiKey = config('services.ml_service.api_key', env('ML_SERVICE_API_KEY', 'change_me'));

        $sessionId = (string) ($payload['session_id'] ?? Str::ulid());
        $internalToken = $this->tokenService->mintToken($user, ['assistant:chat']);
        $rawToken = cache("assistant_token:{$internalToken->id}");

        $toolResults = [];
        $confirmedActions = $payload['confirmed_actions'] ?? [];
        $analysis = $this->intentService->analyze($payload['message'], $payload['context'] ?? [], $user);

        if ($analysis['help_type'] === AssistantIntentService::HELP_CLARIFY && empty($confirmedActions)) {
            return response()->json([
                'response' => $analysis['response'],
                'session_id' => $sessionId,
                'intent' => $analysis['intent'],
                'help_type' => $analysis['help_type'],
                'confidence' => $analysis['confidence'],
                'feature_refs' => $analysis['feature_refs'],
                'quick_replies' => $analysis['quick_replies'],
                'clarifying_options' => $analysis['clarifying_options'],
                'navigation' => $analysis['navigation'],
                'tool_calls' => [],
                'requires_confirmation' => false,
            ]);
        }

        if ($analysis['low_confidence']) {
            $this->intentService->recordDocumentationGap(
                $sessionId,
                $payload['message'],
                $analysis['resolved_intent'],
                $analysis['confidence'],
                $user
            );
        }

        $availableTools = app(AgentToolController::class)->availableToolsForUser($user);

        foreach ($confirmedActions as $action) {
            $toolName = $action['tool'];
            $arguments = $action['arguments'] ?? [];

            $toolResponse = Http::timeout(30)
                ->withHeaders([
                    'X-Assistant-Token' => $rawToken,
                    'X-Assistant-Session' => $sessionId,
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$mlServiceUrl}/agents/crm/chat", [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles()->pluck('name')->toArray(),
                    ],
                    'message' => '[confirmed action execution]',
                    'session_id' => $sessionId,
                    'context' => [
                        'confirmed_tool' => $toolName,
                        'confirmed_arguments' => $arguments,
                        'original_message' => $payload['message'],
                    ],
                ]);

            if ($toolResponse->successful()) {
                $data = $toolResponse->json();
                $toolResults[] = [
                    'tool' => $toolName,
                    'status' => 'executed',
                    'result' => $data['crm_response']['tool_calls'][0]['result'] ?? null,
                ];
            } else {
                $toolResults[] = [
                    'tool' => $toolName,
                    'status' => 'failed',
                    'error' => $toolResponse->body(),
                ];
            }
        }

        try {
            $mlPayload = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles()->pluck('name')->toArray(),
                ],
                'message' => $payload['message'],
                'session_id' => $sessionId,
                'context' => $payload['context'] ?? [],
                'confirmed_actions' => $confirmedActions,
                'tool_results' => $toolResults,
                'system_prompt' => $this->intentService->systemPrompt(),
                'feature_index' => $this->intentService->featureIndex(),
                'intent_analysis' => $analysis,
                'retrieved_documents' => $analysis['articles'],
                'available_tools' => $availableTools['tools'],
            ];

            $response = Http::timeout(60)
                ->withHeaders([
                    'X-Assistant-Token' => $rawToken,
                    'X-Assistant-Session' => $sessionId,
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$mlServiceUrl}/agents/crm/chat", $mlPayload);

            if (! $response->successful()) {
                Log::warning('ML service chat endpoint failed', [
                    'status' => $response->status(),
                    'user_id' => $user->id,
                ]);

                return $this->fallbackResponse($user, $analysis, $sessionId);
            }

            $data = $response->json();
            $crmResponse = $data['crm_response'] ?? $data;

            $applicablePermissions = $user->getAllPermissions()->pluck('name')->toArray();
            $toolsToCall = $crmResponse['tools_to_call'] ?? [];
            $assistantResponse = array_merge([
                'response' => $crmResponse['response'] ?? $data['response'] ?? 'No response generated.',
                'session_id' => $crmResponse['session_id'] ?? $sessionId,
                'intent' => $crmResponse['intent'] ?? $analysis['intent'],
                'help_type' => $crmResponse['help_type'] ?? $analysis['help_type'],
                'confidence' => $crmResponse['confidence'] ?? $analysis['confidence'],
                'feature_refs' => $crmResponse['feature_refs'] ?? $analysis['feature_refs'],
                'quick_replies' => $crmResponse['quick_replies'] ?? $analysis['quick_replies'],
                'articles' => $crmResponse['articles'] ?? $analysis['articles'],
                'low_confidence' => ($crmResponse['low_confidence'] ?? false) || $analysis['low_confidence'],
                'decomposed_intents' => $crmResponse['decomposed_intents'] ?? $analysis['decomposed_intents'],
                'applicable_permissions' => $applicablePermissions,
                'available_tools' => $availableTools['tools'],
                'navigation' => $analysis['navigation'] ?? ($crmResponse['navigation'] ?? null),
                'tool_calls' => $toolsToCall,
                'requires_confirmation' => $toolsToCall[0]['required_confirmation'] ?? false,
                'tool_results' => $toolResults,
            ], $toolResults ? ['executed_actions' => $toolResults] : []);

            return response()->json($assistantResponse);
        } catch (\Throwable $e) {
            Log::error('Assistant chat proxy failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return $this->fallbackResponse($user, $analysis, $sessionId);
        }
    }

    public function proactive(Request $request): JsonResponse
    {
        $user = $request->user();
        $proactiveKey = "assistant:proactive:{$user->id}";
        $items = null;

        try {
            $items = redis()->get($proactiveKey);
            redis()->del($proactiveKey);
        } catch (\Throwable $e) {
            Log::warning('Assistant proactive Redis read failed', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);
        }

        if (! $items) {
            $items = Cache::pull($proactiveKey);
        }

        if (! $items) {
            return response()->json(['proactive_items' => []]);
        }

        $data = json_decode($items, true);
        return response()->json(['proactive_items' => $data ?? []]);
    }

    public function feedback(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'session_id' => 'required|string|ulid',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'sometimes|string|max:1000',
            'message' => 'sometimes|string',
        ]);

        $conversation = $user->assistantConversations()->where('session_id', $validated['session_id'])->first();

        if ($conversation) {
            $conversation->update([
                'feedback_comment' => $validated['comment'] ?? null,
                'feedback_negative' => $conversation->feedback_negative + ($validated['rating'] <= 3 ? 1 : 0),
                'feedback_positive' => $conversation->feedback_positive + ($validated['rating'] >= 4 ? 1 : 0),
                'ended_at' => now(),
            ]);
        } else {
            $user->assistantConversations()->create([
                'session_id' => $validated['session_id'],
                'rating' => $validated['rating'],
                'feedback_comment' => $validated['comment'] ?? null,
                'feedback_negative' => $validated['rating'] <= 3 ? 1 : 0,
                'feedback_positive' => $validated['rating'] >= 4 ? 1 : 0,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function flagLowConfidence(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'session_id' => 'required|string|ulid',
            'user_query' => 'required|string|max:1000',
            'resolved_intent' => 'required|string|max:100',
            'confidence_score' => 'required|string|max:50',
        ]);

        \App\Models\AssistantLowConfidenceRoute::create([
            'session_id' => $validated['session_id'],
            'user_id' => $user?->id,
            'user_query' => $validated['user_query'],
            'resolved_intent' => $validated['resolved_intent'],
            'confidence_score' => $validated['confidence_score'],
            'flagged' => true,
        ]);

        return response()->json(['success' => true]);
    }

    private function fallbackResponse(User $user, array $analysis = [], ?string $sessionId = null): JsonResponse
    {
        try {
            $request = request();
            $query = \App\Models\KnowledgeBaseArticle::published()
                ->where('title', 'like', '%'.$request->input('message', '').'%')
                ->orWhere('body', 'like', '%'.$request->input('message', '').'%')
                ->limit(3)
                ->get(['id', 'title', 'slug']);

            $articles = $query->map(fn ($a) => [
                'title' => $a->title,
                'url' => '/docs/'.$a->slug,
            ])->toArray();
        } catch (\Throwable $e) {
            $articles = [];
        }

        return response()->json([
            'response' => $analysis['response'] ?? 'I\'m currently experiencing technical difficulties. Here are some relevant documentation articles while I recover:',
            'session_id' => $sessionId ?? Str::ulid(),
            'fallback' => true,
            'error_code' => 'ml_service_unavailable',
            'intent' => $analysis['intent'] ?? null,
            'help_type' => $analysis['help_type'] ?? null,
            'confidence' => $analysis['confidence'] ?? null,
            'feature_refs' => $analysis['feature_refs'] ?? [],
            'quick_replies' => $analysis['quick_replies'] ?? [],
            'articles' => $articles ?: ($analysis['articles'] ?? []),
            'low_confidence' => $analysis['low_confidence'] ?? false,
            'navigation' => $analysis['navigation'] ?? null,
            'tool_calls' => [],
            'requires_confirmation' => false,
        ]);
    }
}
