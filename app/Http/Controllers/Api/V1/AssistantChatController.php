<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssistantLowConfidenceRoute;
use App\Models\User;
use App\Services\AssistantTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssistantChatController extends Controller
{
    public function __construct(
        protected AssistantTokenService $tokenService,
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
            'message' => 'required|string|max:4000',
            'session_id' => 'nullable|string|max:255',
            'context' => 'sometimes|array',
            'confirmed_actions' => 'sometimes|array',
            'confirmed_actions.*.tool' => 'required|string',
            'confirmed_actions.*.arguments' => 'sometimes|array',
        ]);

        $mlServiceUrl = rtrim(config('services.ml_service.url', env('ML_SERVICE_URL', 'http://ml-agents:8000')), '/');
        $apiKey = config('services.ml_service.api_key', env('ML_SERVICE_API_KEY', 'change_me'));

        $sessionId = (string) ($payload['session_id'] ?? Str::ulid());
        $internalToken = $this->tokenService->mintToken($user, ['assistant:chat']);
        $rawToken = cache("assistant_token:{$internalToken->id}");

        $toolResults = [];
        $confirmedActions = $payload['confirmed_actions'] ?? [];

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

                return $this->fallbackResponse($user);
            }

            $data = $response->json();
            $crmResponse = $data['crm_response'] ?? $data;

            $applicablePermissions = $user->getAllPermissions()->pluck('name')->toArray();

            return response()->json(array_merge([
                'response' => $crmResponse['response'] ?? $data['response'] ?? 'No response generated.',
                'session_id' => $crmResponse['session_id'] ?? $sessionId,
                'intent' => $crmResponse['intent'] ?? null,
                'tool_calls' => $crmResponse['tools_to_call'] ?? [],
                'requires_confirmation' => $crmResponse['tools_to_call'][0]['required_confirmation'] ?? false,
                'confidence' => $crmResponse['confidence'] ?? null,
                'applicable_permissions' => $applicablePermissions,
                'navigation' => $crmResponse['navigation'] ?? null,
                'tool_results' => $toolResults,
            ], $toolResults ? ['executed_actions' => $toolResults] : []));
        } catch (\Throwable $e) {
            Log::error('Assistant chat proxy failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return $this->fallbackResponse($user);
        }
    }

    public function proactive(Request $request): JsonResponse
    {
        $user = $request->user();
        $proactiveKey = "assistant:proactive:{$user->id}";
        $items = redis()->get($proactiveKey);
        redis()->del($proactiveKey);

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

    private function fallbackResponse(User $user): JsonResponse
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
            'response' => 'I\'m currently experiencing technical difficulties. Here are some relevant documentation articles while I recover:',
            'fallback' => true,
            'error_code' => 'ml_service_unavailable',
            'articles' => $articles,
        ]);
    }
}
