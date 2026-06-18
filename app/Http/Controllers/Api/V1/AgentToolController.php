<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AgentToolController extends Controller
{
    protected array $tools = [
        'contacts.search' => ['controller' => ContactController::class, 'method' => 'index', 'tier' => 'read'],
        'contacts.get' => ['controller' => ContactController::class, 'method' => 'show', 'tier' => 'read'],
        'contacts.timeline' => ['controller' => ContactController::class, 'method' => 'timeline', 'tier' => 'read'],
        'deals.search' => ['controller' => DealController::class, 'method' => 'index', 'tier' => 'read'],
        'deals.get' => ['controller' => DealController::class, 'method' => 'show', 'tier' => 'read'],
        'deals.move_stage' => ['controller' => DealController::class, 'method' => 'moveStage', 'tier' => 'write-reversible'],
        'deals.create' => ['controller' => DealController::class, 'method' => 'store', 'tier' => 'write-reversible'],
        'accounts.search' => ['controller' => AccountController::class, 'method' => 'index', 'tier' => 'read'],
        'accounts.get' => ['controller' => AccountController::class, 'method' => 'show', 'tier' => 'read'],
        'tickets.search' => ['controller' => TicketController::class, 'method' => 'index', 'tier' => 'read'],
        'tickets.get' => ['controller' => TicketController::class, 'method' => 'show', 'tier' => 'read'],
        'tickets.create' => ['controller' => TicketController::class, 'method' => 'store', 'tier' => 'write-reversible'],
        'tickets.update_status' => ['controller' => TicketController::class, 'method' => 'updateStatus', 'tier' => 'write-reversible'],
        'activities.create' => ['controller' => ActivityController::class, 'method' => 'store', 'tier' => 'write-reversible'],
        'segments.preview' => ['controller' => SegmentController::class, 'method' => 'preview', 'tier' => 'read'],
        'segments.preview_count' => ['controller' => SegmentController::class, 'method' => 'previewSegment', 'tier' => 'read'],
        'kb.search' => ['controller' => KnowledgeBaseController::class, 'method' => 'index', 'tier' => 'read'],
        'dashboards.summary' => ['controller' => AnalyticsApiController::class, 'method' => 'dashboard', 'tier' => 'read'],
        'analytics.metric' => ['controller' => AnalyticsApiController::class, 'method' => 'dashboardWidgets', 'tier' => 'read'],
        'reports.run' => ['controller' => ReportBuilderController::class, 'method' => 'run', 'tier' => 'read'],
        'contracts.search' => ['controller' => ContractController::class, 'method' => 'indexApi', 'tier' => 'read'],
        'contracts.get_status' => ['controller' => ContractController::class, 'method' => 'showApi', 'tier' => 'read'],
        'loyalty.get_balance' => ['controller' => LoyaltyProgramController::class, 'method' => 'getBalance', 'tier' => 'read'],
        'clv.get_score' => ['controller' => ClvAnalyticsController::class, 'method' => 'show', 'tier' => 'read'],
        'users.my_permissions' => ['controller' => UserController::class, 'method' => 'permissions', 'tier' => 'read'],
        'integrations.get_status' => ['controller' => IntegrationController::class, 'method' => 'index', 'tier' => 'read'],
        'webhooks.get_delivery_log' => ['controller' => WebhookController::class, 'method' => 'deliveries', 'tier' => 'read'],
        'notifications.get_unread' => ['controller' => NotificationController::class, 'method' => 'unread', 'tier' => 'read'],
        'calendar.upcoming' => ['controller' => CalendarController::class, 'method' => 'upcoming', 'tier' => 'read'],
        'comments.post' => ['controller' => CommentController::class, 'method' => 'store', 'tier' => 'write-significant'],
        'tasks.create' => ['controller' => TaskController::class, 'method' => 'store', 'tier' => 'write-reversible'],
        'invoices.search' => ['controller' => InvoiceController::class, 'method' => 'index', 'tier' => 'read'],
        'invoices.get_ledger' => ['controller' => InvoiceController::class, 'method' => 'ledger', 'tier' => 'read'],
    ];

    protected array $destructiveActions = [
        'contacts.bulk-delete',
        'integrations.disconnect',
        'contracts.purge',
        'users.delete',
        'webhooks.purge',
    ];

    public function handle(Request $request, string $tool): JsonResponse
    {
        $user = $request->attributes->get('assistant_user');

        $toolDefinition = $this->tools[$tool] ?? null;

        if (! $toolDefinition) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => "Tool '{$tool}' is not registered in the agent tool API.",
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        if (in_array($tool, $this->destructiveActions, true)) {
            return response()->json([
                'error' => [
                    'code' => 'action_not_permitted',
                    'message' => "Tool '{$tool}' is not permitted via assistant. Navigate to the relevant screen to perform this action.",
                    'navigation' => $this->guessNavigationForDestructiveAction($tool),
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $tier = $toolDefinition['tier'];

        if ($tier === 'write-significant') {
            $confirmed = $request->input('confirmed', false);

            if (! $confirmed) {
                return response()->json([
                    'requires_confirmation' => true,
                    'tool' => $tool,
                    'tier' => $tier,
                    'message' => 'This action requires your explicit confirmation before I can execute it.',
                ], Response::HTTP_PRECONDITION_REQUIRED);
            }
        }

        try {
            $start = microtime(true);
            $controllerClass = $toolDefinition['controller'];
            $method = $toolDefinition['method'];

            if (! class_exists($controllerClass) || ! method_exists($controllerClass, $method)) {
                return response()->json([
                    'error' => [
                        'code' => 'not_implemented',
                        'message' => "Tool '{$tool}' implementation is pending.",
                    ],
                ], Response::HTTP_NOT_IMPLEMENTED);
            }

            $controller = app($controllerClass);

            $this->enforceRbac($user, $toolDefinition);

            $laravelRequest = Request::create(
                $request->getPathInfo(),
                $request->getMethod(),
                $request->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            );
            $laravelRequest->setUserResolver(function () use ($user) {
                return $user;
            });

            $response = $controller->{$method}($laravelRequest);

            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            $status = $response->getStatusCode();
            $content = json_decode($response->getContent(), true) ?: ['message' => $response->getContent()];

            if ($status >= 400) {
                return response()->json([
                    'error' => [
                        'code' => 'tool_execution_failed',
                        'message' => $content['message'] ?? 'Tool execution failed.',
                        'tool' => $tool,
                        'status' => $status,
                    ],
                ], $status);
            }

            if ($tier !== 'read') {
                $recordUrl = $this->extractRecordUrl($content);
                if ($recordUrl && is_array($content)) {
                    $content['record_url'] = $recordUrl;
                }
                $cascadingActions = $this->inferCascadingActions($tool, $user);
                if ($cascadingActions) {
                    $content['cascading_actions'] = $cascadingActions;
                }
            }

            $this->logToolCall($tool, $tier, $user?->id, $request->header('X-Assistant-Session'), $latencyMs, true, null, $request->all());

            return response()->json(array_merge([
                'tool' => $tool,
                'tier' => $tier,
                'status' => 'success',
                'latency_ms' => $latencyMs,
            ], $content));
        } catch (UnauthorizedException $e) {
            Log::warning('Assistant tool RBAC denied', ['tool' => $tool, 'user_id' => $user?->id]);
            return response()->json([
                'error' => [
                    'code' => 'permission_denied',
                    'message' => "You don't have permission to use this tool. Your manager can assist with this.",
                    'tool' => $tool,
                ],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            Log::error('Assistant tool execution error', ['tool' => $tool, 'error' => $e->getMessage(), 'user_id' => $user?->id]);
            return response()->json([
                'error' => [
                    'code' => 'internal_error',
                    'message' => "An unexpected error occurred while executing '{$tool}'. Please try again or perform the action directly in the UI.",
                    'tool' => $tool,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function availableTools(Request $request): JsonResponse
    {
        $user = $request->attributes->get('assistant_user');

        if (! $user) {
            return response()->json(['tools' => [], 'permissions' => []]);
        }

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        $roleTools = [];
        foreach ($this->tools as $name => $definition) {
            try {
                $this->enforceRbac($user, $definition);
                $roleTools[] = [
                    'name' => $name,
                    'tier' => $definition['tier'],
                ];
            } catch (UnauthorizedException $e) {
                // Skip tools the user cannot access
            }
        }

        return response()->json([
            'tools' => $roleTools,
            'permissions' => $permissions,
        ]);
    }

    private function enforceRbac(?User $user, array $toolDefinition): void
    {
        if (! $user) {
            throw UnauthorizedException::forPermission('assistant.tools.' . $toolDefinition['controller']);
        }

        $controllerClass = $toolDefinition['controller'];
        $method = $toolDefinition['method'];

        $modelClass = $this->guessModelClass($controllerClass);
        $action = $this->guessActionFromMethod($method);

        if ($modelClass && $action) {
            if (! $user->can($action, $modelClass)) {
                throw UnauthorizedException::forPermission("{$action} {$modelClass}");
            }
        }
    }

    private function guessModelClass(string $controllerClass): ?string
    {
        $map = [
            ContactController::class => \App\Models\Contact::class,
            DealController::class => \App\Models\Deal::class,
            AccountController::class => \App\Models\Account::class,
            TicketController::class => \App\Models\Ticket::class,
            ActivityController::class => \App\Models\Activity::class,
            KnowledgeBaseController::class => \App\Models\KnowledgeBaseArticle::class,
            AnalyticsApiController::class => \App\Models\Dashboard::class,
            ReportBuilderController::class => \App\Models\ReportDefinition::class,
            CommentController::class => \App\Models\Comment::class,
            ContractController::class => \App\Models\Contract::class,
            LoyaltyProgramController::class => \App\Models\LoyaltyProgram::class,
            ClvAnalyticsController::class => \App\Models\Contact::class,
            UserController::class => \App\Models\User::class,
            IntegrationController::class => \App\Models\Integration::class,
            WebhookController::class => \App\Models\Webhook::class,
            NotificationController::class => \App\Models\Notification::class,
            CalendarController::class => \App\Models\Activity::class,
            TaskController::class => \App\Models\Activity::class,
            InvoiceController::class => \App\Models\Invoice::class,
            SegmentController::class => \App\Models\Segment::class,
        ];

        return $map[$controllerClass] ?? null;
    }

    private function guessActionFromMethod(string $method): ?string
    {
        $map = [
            'index' => 'viewAny',
            'show' => 'view',
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
            'moveStage' => 'update',
            'updateStatus' => 'update',
            'timeline' => 'view',
            'previewCount' => 'viewAny',
            'previewSegment' => 'viewAny',
            'dashboard' => 'viewAny',
            'run' => 'viewAny',
            'getBalance' => 'view',
            'unread' => 'viewAny',
            'upcoming' => 'viewAny',
            'permissions' => 'view',
        ];

        return $map[$method] ?? null;
    }

    private function extractRecordUrl(array $content): ?string
    {
        if (isset($content['id'])) {
            $id = $content['id'];
            $type = strtolower(class_basename($this));
            return "/{$type}s/{$id}";
        }

        return null;
    }

    private function inferCascadingActions(string $tool, ?User $user): array
    {
        $actions = [];

        if ($tool === 'deals.move_stage') {
            $actions[] = 'Create follow-up task for deal owner';
            $actions[] = 'Notify assigned team members';
        }

        if ($tool === 'tickets.create') {
            $actions[] = 'Assign SLA to ticket';
            $actions[] = 'Notify assigned agent';
        }

        return $actions;
    }

    private function guessNavigationForDestructiveAction(string $tool): string
    {
        $map = [
            'contacts.bulk-delete' => '/contacts',
            'integrations.disconnect' => '/admin/integrations',
            'contracts.purge' => '/contracts',
            'users.delete' => '/admin/users',
            'webhooks.purge' => '/admin/webhooks',
        ];

        return $map[$tool] ?? '/';
    }

    private function logToolCall(string $tool, string $tier, ?string $userId, ?string $sessionId, int $latencyMs, bool $success, ?string $error, array $input): void
    {
        try {
            Log::info('Agent tool call', [
                'tool' => $tool,
                'tier' => $tier,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'latency_ms' => $latencyMs,
                'success' => $success,
                'error' => $error,
                'actor_type' => 'assistant',
                'input' => $input,
            ]);
        } catch (\Throwable $e) {
            // best-effort logging
        }
    }
}

