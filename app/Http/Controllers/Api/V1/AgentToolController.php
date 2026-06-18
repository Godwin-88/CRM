<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AssistantConversation;
use App\Models\AssistantToolCall;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class AgentToolController extends Controller
{
    protected const TOOL_SCHEMA_VERSION = '2026-06-18';

    protected array $tools = [
        'contacts.search' => [
            'controller' => ContactController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['contacts.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'maxLength' => 255],
                    'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['lead', 'prospect', 'customer', 'partner']],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'churned', 'reactivated']],
                    'source' => ['type' => 'string'],
                    'loyalty_tier' => ['type' => 'string'],
                    'owner_id' => ['type' => 'string'],
                    'created_from' => ['type' => 'string'],
                    'created_to' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Paginated contact collection.'],
        ],
        'contacts.get' => [
            'controller' => ContactController::class,
            'method' => 'show',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['contacts.view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['contact_id'],
                'properties' => ['contact_id' => ['type' => 'string']],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Contact detail record.'],
        ],
        'contacts.timeline' => [
            'controller' => ContactController::class,
            'method' => 'timeline',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.5',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['contacts.view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['contact_id'],
                'properties' => [
                    'contact_id' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Paginated activity timeline.'],
        ],
        'deals.search' => [
            'controller' => DealController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.2 Pipeline',
            'feature_ref' => '4.2.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['deals.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'pipeline_id' => ['type' => 'string'],
                    'stage' => ['type' => 'string'],
                    'owner_id' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'search' => ['type' => 'string', 'maxLength' => 255],
                    'expected_close_from' => ['type' => 'string'],
                    'expected_close_to' => ['type' => 'string'],
                    'value_min' => ['type' => 'number'],
                    'value_max' => ['type' => 'number'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Paginated deal collection.'],
        ],
        'deals.get' => [
            'controller' => DealController::class,
            'method' => 'show',
            'tier' => 'read',
            'module' => '4.2 Pipeline',
            'feature_ref' => '4.2.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['deals.view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['deal_id'],
                'properties' => ['deal_id' => ['type' => 'string']],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Deal detail record.'],
        ],
        'deals.move_stage' => [
            'controller' => DealController::class,
            'method' => 'moveStage',
            'tier' => 'write-reversible',
            'module' => '4.2 Pipeline',
            'feature_ref' => '4.2.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['deals.edit'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['deal_id', 'stage'],
                'properties' => [
                    'deal_id' => ['type' => 'string'],
                    'stage' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Updated deal record.'],
        ],
        'deals.create' => [
            'controller' => DealController::class,
            'method' => 'store',
            'tier' => 'write-reversible',
            'module' => '4.2 Pipeline',
            'feature_ref' => '4.2.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['deals.create'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['title', 'account_id', 'contact_id', 'owner_id'],
                'properties' => [
                    'title' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'pipeline_id' => ['type' => 'string'],
                    'stage' => ['type' => 'string'],
                    'value' => ['type' => 'number'],
                    'currency' => ['type' => 'string', 'maxLength' => 3],
                    'expected_close_date' => ['type' => 'string'],
                    'owner_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Created deal record.'],
        ],
        'accounts.search' => [
            'controller' => AccountController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Account::class, 'action' => 'viewAny'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'industry' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'country' => ['type' => 'string'],
                    'account_manager_id' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Paginated account collection.'],
        ],
        'accounts.get' => [
            'controller' => AccountController::class,
            'method' => 'show',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Account::class, 'action' => 'view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['account_id'],
                'properties' => ['account_id' => ['type' => 'string']],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Account detail record.'],
        ],
        'tickets.search' => [
            'controller' => TicketController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.6 Support',
            'feature_ref' => '4.6.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Ticket::class, 'action' => 'viewAny'],
            'roles' => ['admin', 'manager', 'agent'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'maxLength' => 255],
                    'status' => ['type' => 'string'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'category_id' => ['type' => 'string'],
                    'assigned_to' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'sla' => ['type' => 'string', 'enum' => ['breached']],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Paginated ticket collection.'],
        ],
        'tickets.get' => [
            'controller' => TicketController::class,
            'method' => 'show',
            'tier' => 'read',
            'module' => '4.6 Support',
            'feature_ref' => '4.6.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Ticket::class, 'action' => 'viewAny'],
            'roles' => ['admin', 'manager', 'agent'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['ticket_id'],
                'properties' => ['ticket_id' => ['type' => 'string']],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Ticket detail record.'],
        ],
        'tickets.create' => [
            'controller' => TicketController::class,
            'method' => 'store',
            'tier' => 'write-reversible',
            'module' => '4.6 Support',
            'feature_ref' => '4.6.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Ticket::class, 'action' => 'create'],
            'roles' => ['admin', 'manager', 'agent'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['subject', 'contact_id'],
                'properties' => [
                    'subject' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'category_id' => ['type' => 'string'],
                    'assigned_to' => ['type' => 'string'],
                    'form_response' => ['type' => 'array'],
                    'is_agent_created' => ['type' => 'boolean'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Created ticket record.'],
        ],
        'tickets.update_status' => [
            'controller' => TicketController::class,
            'method' => 'updateStatus',
            'tier' => 'write-reversible',
            'module' => '4.6 Support',
            'feature_ref' => '4.6.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Ticket::class, 'action' => 'update'],
            'roles' => ['admin', 'manager', 'agent'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['ticket_id', 'status'],
                'properties' => [
                    'ticket_id' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['open', 'in_progress', 'waiting_on_customer', 'resolved', 'closed']],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Updated ticket record.'],
        ],
        'activities.create' => [
            'controller' => ActivityController::class,
            'method' => 'store',
            'tier' => 'write-reversible',
            'module' => '4.2 Pipeline',
            'feature_ref' => '4.2.7',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'rbac' => ['model' => Deal::class, 'action' => 'update'],
            'roles' => ['admin', 'manager', 'agent'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['subject'],
                'properties' => [
                    'subject' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['call', 'email', 'task', 'meeting']],
                    'due_at' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'deal_id' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    'assigned_to' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Created activity record.'],
        ],
        'segments.preview' => [
            'controller' => SegmentController::class,
            'method' => 'preview',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['segments.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'filters' => ['type' => 'array'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Segment preview payload.'],
        ],
        'segments.preview_count' => [
            'controller' => SegmentController::class,
            'method' => 'previewSegment',
            'tier' => 'read',
            'module' => '4.1 Contacts/Accounts',
            'feature_ref' => '4.1.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['segments.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'segment_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Segment preview count.'],
        ],
        'kb.search' => [
            'controller' => KnowledgeBaseController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.6 Support',
            'feature_ref' => '4.6.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Knowledge base article collection.'],
        ],
        'dashboards.summary' => [
            'controller' => AnalyticsApiController::class,
            'method' => 'dashboard',
            'tier' => 'read',
            'module' => '4.7 Analytics',
            'feature_ref' => '4.7.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'dashboard_id' => ['type' => 'string'],
                    'filters' => ['type' => 'array'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Dashboard summary payload.'],
        ],
        'analytics.metric' => [
            'controller' => AnalyticsApiController::class,
            'method' => 'dashboardWidgets',
            'tier' => 'read',
            'module' => '4.7 Analytics',
            'feature_ref' => '4.7.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'filters' => ['type' => 'array'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Analytics widget payload.'],
        ],
        'reports.run' => [
            'controller' => ReportBuilderController::class,
            'method' => 'run',
            'tier' => 'read',
            'module' => '4.7 Analytics',
            'feature_ref' => '4.7.7',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'required' => ['report_id'],
                'properties' => [
                    'report_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Report run result.'],
        ],
        'contracts.search' => [
            'controller' => ContractController::class,
            'method' => 'indexApi',
            'tier' => 'read',
            'module' => '4.8 Contracts',
            'feature_ref' => '4.8.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['contracts.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Contract collection.'],
        ],
        'contracts.get_status' => [
            'controller' => ContractController::class,
            'method' => 'showApi',
            'tier' => 'read',
            'module' => '4.8 Contracts',
            'feature_ref' => '4.8.6',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['contracts.view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['contract_id'],
                'properties' => [
                    'contract_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Contract status payload.'],
        ],
        'loyalty.get_balance' => [
            'controller' => LoyaltyProgramController::class,
            'method' => 'getBalance',
            'tier' => 'read',
            'module' => '4.5 Experience/Loyalty',
            'feature_ref' => '4.5.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['loyalty.adjust'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['contact_id'],
                'properties' => [
                    'contact_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Loyalty balance payload.'],
        ],
        'clv.get_score' => [
            'controller' => ClvAnalyticsController::class,
            'method' => 'show',
            'tier' => 'read',
            'module' => '4.7 Analytics',
            'feature_ref' => '4.7.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'required' => ['contact_id'],
                'properties' => [
                    'contact_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'CLV score payload.'],
        ],
        'users.my_permissions' => [
            'controller' => UserController::class,
            'method' => 'permissions',
            'tier' => 'read',
            'module' => '4.10 Security',
            'feature_ref' => '4.10.4',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [],
            ],
            'output_schema' => ['type' => 'object', 'description' => "Current user's permissions and roles."],
        ],
        'integrations.get_status' => [
            'controller' => IntegrationController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.11 Integrations',
            'feature_ref' => '4.11.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['integrations.manage'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Integration status collection.'],
        ],
        'webhooks.get_delivery_log' => [
            'controller' => WebhookController::class,
            'method' => 'deliveries',
            'tier' => 'read',
            'module' => '4.11 Integrations',
            'feature_ref' => '4.11.3',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['integrations.manage'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['webhook_id'],
                'properties' => [
                    'webhook_id' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Webhook delivery log.'],
        ],
        'notifications.get_unread' => [
            'controller' => NotificationController::class,
            'method' => 'unread',
            'tier' => 'read',
            'module' => '4.12 Collaboration',
            'feature_ref' => '4.12.2',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Unread notification collection.'],
        ],
        'calendar.upcoming' => [
            'controller' => CalendarController::class,
            'method' => 'upcoming',
            'tier' => 'read',
            'module' => '4.12 Collaboration',
            'feature_ref' => '4.12.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'from' => ['type' => 'string'],
                    'to' => ['type' => 'string'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Upcoming calendar entries.'],
        ],
        'comments.post' => [
            'controller' => CommentController::class,
            'method' => 'store',
            'tier' => 'write-significant',
            'module' => '4.12 Collaboration',
            'feature_ref' => '4.12.4',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'required' => ['type', 'id', 'body'],
                'properties' => [
                    'type' => ['type' => 'string'],
                    'id' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                ],
            ],
            'roles' => ['admin', 'manager', 'employee'],
            'output_schema' => ['type' => 'object', 'description' => 'Created comment.'],
        ],
        'tasks.create' => [
            'controller' => ActivityController::class,
            'method' => 'store',
            'tier' => 'write-reversible',
            'module' => '4.12 Collaboration',
            'feature_ref' => '4.12.4',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'input_schema' => [
                'type' => 'object',
                'required' => ['subject'],
                'properties' => [
                    'subject' => ['type' => 'string'],
                    'due_at' => ['type' => 'string'],
                    'contact_id' => ['type' => 'string'],
                    'deal_id' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'assigned_to' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                ],
            ],
            'roles' => ['admin', 'manager', 'agent', 'employee'],
            'output_schema' => ['type' => 'object', 'description' => 'Created task activity.'],
        ],
        'invoices.search' => [
            'controller' => InvoiceController::class,
            'method' => 'index',
            'tier' => 'read',
            'module' => '4.9 Back-office',
            'feature_ref' => '4.9.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['invoices.view'],
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'account_id' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Invoice collection.'],
        ],
        'invoices.get_ledger' => [
            'controller' => InvoiceController::class,
            'method' => 'ledger',
            'tier' => 'read',
            'module' => '4.9 Back-office',
            'feature_ref' => '4.9.1',
            'schema_version' => self::TOOL_SCHEMA_VERSION,
            'permissions' => ['invoices.view'],
            'input_schema' => [
                'type' => 'object',
                'required' => ['account_id'],
                'properties' => [
                    'account_id' => ['type' => 'string'],
                ],
            ],
            'output_schema' => ['type' => 'object', 'description' => 'Account invoice ledger.'],
        ],
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
        $input = $request->all();

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

        $validationError = $this->validateToolInput($toolDefinition['input_schema'] ?? ['type' => 'object'], $input);

        if ($validationError) {
            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Tool input validation failed.',
                    'tool' => $tool,
                    'details' => $validationError,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
                $request->fullUrl(),
                $request->getMethod(),
                $input,
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            );
            $laravelRequest->setUserResolver(function () use ($user) {
                return $user;
            });

            $arguments = $this->resolveToolArguments($laravelRequest, $controllerClass, $method, $input);
            if ($arguments instanceof JsonResponse) {
                return $arguments;
            }

            $response = $controller->{$method}(...$arguments);

            $latencyMs = (int) ((microtime(true) - $start) * 1000);

            $status = $response->getStatusCode();
            $content = json_decode($response->getContent(), true) ?: ['message' => $response->getContent()];

            if ($status >= 400) {
                $this->logToolCall($tool, $tier, $user?->id, $request->header('X-Assistant-Session'), $latencyMs, false, $content['message'] ?? 'Tool execution failed.', $input, $content);

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
                $recordUrl = $this->extractRecordUrl($content, $tool);
                if ($recordUrl && is_array($content)) {
                    $content['record_url'] = $recordUrl;
                }
                $cascadingActions = $this->inferCascadingActions($tool, $user);
                if ($cascadingActions) {
                    $content['cascading_actions'] = $cascadingActions;
                }
            }

            $this->logToolCall($tool, $tier, $user?->id, $request->header('X-Assistant-Session'), $latencyMs, true, null, $input, $content);

            return response()->json(array_merge([
                'tool' => $tool,
                'tier' => $tier,
                'status' => 'success',
                'latency_ms' => $latencyMs,
            ], $content));
        } catch (UnauthorizedException $e) {
            Log::warning('Assistant tool RBAC denied', ['tool' => $tool, 'user_id' => $user?->id]);
            $this->logToolCall($tool, $toolDefinition['tier'] ?? 'unknown', $user?->id, $request->header('X-Assistant-Session'), 0, false, $e->getMessage(), $input, []);
            return response()->json([
                'error' => [
                    'code' => 'permission_denied',
                    'message' => "You don't have permission to use this tool. Your manager can assist with this.",
                    'tool' => $tool,
                ],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            Log::error('Assistant tool execution error', ['tool' => $tool, 'error' => $e->getMessage(), 'user_id' => $user?->id]);
            $this->logToolCall($tool, $toolDefinition['tier'] ?? 'unknown', $user?->id, $request->header('X-Assistant-Session'), 0, false, $e->getMessage(), $input, []);
            return response()->json([
                'error' => [
                    'code' => 'internal_error',
                    'message' => "An unexpected error occurred while executing '{$tool}'. Please try again or perform the action directly in the UI.",
                    'tool' => $tool,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function availableToolsForUser(?User $user): array
    {
        if (! $user) {
            return ['tools' => [], 'permissions' => []];
        }

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        $roleTools = [];

        foreach ($this->tools as $name => $definition) {
            try {
                $this->enforceRbac($user, $definition);
                $roleTools[] = [
                    'name' => $name,
                    'tier' => $definition['tier'],
                    'module' => $definition['module'] ?? null,
                    'feature_ref' => $definition['feature_ref'] ?? null,
                    'schema_version' => $definition['schema_version'] ?? null,
                    'input_schema' => $definition['input_schema'] ?? ['type' => 'object'],
                    'output_schema' => $definition['output_schema'] ?? ['type' => 'object'],
                    'roles' => $definition['roles'] ?? [],
                    'requires_confirmation' => $definition['tier'] === 'write-significant',
                ];
            } catch (UnauthorizedException) {
            }
        }

        return [
            'tools' => $roleTools,
            'permissions' => $permissions,
        ];
    }

    public function availableTools(Request $request): JsonResponse
    {
        return response()->json($this->availableToolsForUser($request->attributes->get('assistant_user')));
    }

    public function resolveNavigationReference(?User $user, string $type, string $query, array $filters = []): array
    {
        if (! $user) {
            return [];
        }

        $type = Str::lower($type);
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $builder = match ($type) {
            'contact' => $this->contactSearchQuery($query, $filters),
            'account' => $this->accountSearchQuery($query, $filters),
            'deal' => $this->dealSearchQuery($query, $filters),
            'ticket' => $this->ticketSearchQuery($query, $filters),
            'contract' => $this->contractSearchQuery($query, $filters),
            default => null,
        };

        if (! $builder) {
            return [];
        }

        return $builder
            ->limit(6)
            ->get()
            ->map(fn (Model $record) => $this->formatNavigationMatch($user, $type, $record))
            ->filter(fn (array $match) => (bool) $match['allowed'])
            ->values()
            ->all();
    }

    private function validateToolInput(array $schema, array $input): array
    {
        $errors = [];

        foreach ($schema['required'] ?? [] as $field) {
            if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        foreach ($schema['properties'] ?? [] as $field => $rules) {
            if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                continue;
            }

            $errors = array_merge($errors, $this->validateFieldValue($field, $input[$field], $rules));
        }

        return $errors;
    }

    private function validateFieldValue(string $field, mixed $value, array $rules): array
    {
        $expectedType = $rules['type'] ?? null;
        $actualType = $this->jsonType($value);

        if ($expectedType && $expectedType !== $actualType) {
            return [$field => "Expected {$expectedType}, got {$actualType}."];
        }

        if (isset($rules['enum']) && is_array($rules['enum']) && ! in_array($value, $rules['enum'], true)) {
            return [$field => 'Value is not in the allowed list.'];
        }

        if (isset($rules['maxLength']) && is_string($value) && strlen($value) > (int) $rules['maxLength']) {
            return [$field => "Value must be {$rules['maxLength']} characters or fewer."];
        }

        if (isset($rules['minimum']) && is_numeric($value) && $value < $rules['minimum']) {
            return [$field => "Value must be at least {$rules['minimum']}."];
        }

        if (isset($rules['maximum']) && is_numeric($value) && $value > $rules['maximum']) {
            return [$field => "Value must be no more than {$rules['maximum']}."];
        }

        return [];
    }

    private function jsonType(mixed $value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'number';
        if (is_string($value)) return 'string';
        if (is_array($value) && array_is_list($value)) return 'array';
        if (is_array($value)) return 'object';

        return 'null';
    }

    private function resolveToolArguments(Request $request, string $controllerClass, string $method, array $input): array|JsonResponse
    {
        $arguments = [$request];
        $reflection = new \ReflectionMethod($controllerClass, $method);

        foreach ($reflection->getParameters() as $index => $parameter) {
            if ($index === 0) {
                continue;
            }

            $type = $parameter->getType();

            if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                $name = $parameter->getName();
                $key = $this->parameterInputKey($method, $name);

                if (! array_key_exists($key, $input)) {
                    return response()->json([
                        'error' => [
                            'code' => 'validation_failed',
                            'message' => "Missing required parameter '{$key}'.",
                            'tool' => $parameter->getName(),
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $arguments[] = $input[$key];
                continue;
            }

            $typeName = $type->getName();

            if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                $arguments[] = $request;
                continue;
            }

            if (is_subclass_of($typeName, Model::class)) {
                $key = $this->modelInputKey($typeName);

                if (! array_key_exists($key, $input)) {
                    return response()->json([
                        'error' => [
                            'code' => 'validation_failed',
                            'message' => "Missing required parameter '{$key}'.",
                            'tool' => $typeName,
                        ],
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $arguments[] = $typeName::findOrFail($input[$key]);
                continue;
            }

            $arguments[] = null;
        }

        return $arguments;
    }

    private function modelInputKey(string $modelClass): string
    {
        return match ($modelClass) {
            Contact::class => 'contact_id',
            Account::class => 'account_id',
            Deal::class => 'deal_id',
            Ticket::class => 'ticket_id',
            Contract::class => 'contract_id',
            default => 'id',
        };
    }

    private function parameterInputKey(string $method, string $parameterName): string
    {
        return match ("{$method}:{$parameterName}") {
            'timeline:id' => 'contact_id',
            default => $parameterName,
        };
    }

    private function contactSearchQuery(string $query, array $filters)
    {
        return Contact::query()
            ->with(['owner'])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$query}%"]);
            })
            ->when($filters['account_id'] ?? null, fn ($q, $value) => $q->whereHas('accounts', fn ($qa) => $qa->where('accounts.id', $value)))
            ->when($filters['account_name'] ?? null, fn ($q, $value) => $q->whereHas('accounts', fn ($qa) => $qa->where('accounts.name', 'like', "%{$value}%")));
    }

    private function accountSearchQuery(string $query, array $filters)
    {
        return Account::query()
            ->with(['accountManager'])
            ->where('name', 'like', "%{$query}%")
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value));
    }

    private function dealSearchQuery(string $query, array $filters)
    {
        return Deal::query()
            ->with(['account', 'contact', 'owner', 'pipeline'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('account', fn ($qa) => $qa->where('accounts.name', 'like', "%{$query}%"))
                    ->orWhereHas('contact', fn ($qc) => $qc->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$query}%"]));
            })
            ->when($filters['account_id'] ?? null, fn ($q, $value) => $q->where('account_id', $value))
            ->when($filters['contact_id'] ?? null, fn ($q, $value) => $q->where('contact_id', $value))
            ->when($filters['stage'] ?? null, fn ($q, $value) => $q->where('stage', $value));
    }

    private function ticketSearchQuery(string $query, array $filters)
    {
        return Ticket::query()
            ->with(['contact', 'account', 'assignee', 'category'])
            ->notMerged()
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhereHas('contact', fn ($qc) => $qc->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$query}%"]))
                    ->orWhereHas('account', fn ($qa) => $qa->where('accounts.name', 'like', "%{$query}%"));
            })
            ->when($filters['account_id'] ?? null, fn ($q, $value) => $q->where('account_id', $value))
            ->when($filters['contact_id'] ?? null, fn ($q, $value) => $q->where('contact_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value));
    }

    private function contractSearchQuery(string $query, array $filters)
    {
        return Contract::query()
            ->with(['account', 'contact', 'createdBy'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%")
                    ->orWhereHas('account', fn ($qa) => $qa->where('accounts.name', 'like', "%{$query}%"))
                    ->orWhereHas('contact', fn ($qc) => $qc->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$query}%"]));
            })
            ->when($filters['account_id'] ?? null, fn ($q, $value) => $q->where('account_id', $value))
            ->when($filters['contact_id'] ?? null, fn ($q, $value) => $q->where('contact_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value));
    }

    private function formatNavigationMatch(User $user, string $type, Model $record): array
    {
        $allowed = false;

        try {
            $allowed = $user->can('view', $record);
        } catch (\Throwable) {
            $allowed = false;
        }

        return [
            'type' => $type,
            'id' => $record->getKey(),
            'label' => $this->navigationLabel($type, $record),
            'route' => $this->navigationRoute($type, $record),
            'description' => $this->navigationDescription($type, $record),
            'allowed' => $allowed,
        ];
    }

    private function navigationLabel(string $type, Model $record): string
    {
        return match ($type) {
            'contact' => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: $record->email,
            'account' => $record->name,
            'deal' => $record->title,
            'ticket' => $record->subject,
            'contract' => $record->title,
            default => (string) $record->getKey(),
        };
    }

    private function navigationRoute(string $type, Model $record): string
    {
        return match ($type) {
            'contact' => '/contacts/'.$record->getKey(),
            'account' => '/accounts/'.$record->getKey(),
            'deal' => '/deals/'.$record->getKey(),
            'ticket' => '/support/tickets/'.$record->getKey(),
            'contract' => '/contracts/'.$record->getKey(),
            default => '/',
        };
    }

    private function navigationDescription(string $type, Model $record): string
    {
        return match ($type) {
            'contact' => $record->email ? "Contact: {$record->email}" : ($record->owner?->name ? "Owner: {$record->owner->name}" : 'Contact'),
            'account' => $record->accountManager?->name ? "Manager: {$record->accountManager->name}" : 'Account',
            'deal' => ($record->account?->name ? "{$record->account->name} • " : '').($record->stage ? "Stage: {$record->stage}" : 'Deal'),
            'ticket' => ($record->account?->name ? "{$record->account->name} • " : '')."Status: {$record->status}",
            'contract' => ($record->account?->name ? "{$record->account->name} • " : '')."Status: {$record->status}",
            default => 'Record',
        };
    }

    private function classLevelRbacAction(string $action, mixed $modelOrClass): ?string
    {
        $classLevelActions = [
            Account::class => ['viewAny', 'create'],
            Contact::class => ['viewAny', 'create'],
            Deal::class => ['viewAny', 'create'],
            Ticket::class => ['viewAny', 'create'],
            Contract::class => ['viewAny', 'create'],
            Invoice::class => ['viewAny'],
            Segment::class => ['viewAny'],
            ReportDefinition::class => ['viewAny'],
        ];

        if (! is_string($modelOrClass) || ! isset($classLevelActions[$modelOrClass])) {
            return null;
        }

        return in_array($action, $classLevelActions[$modelOrClass], true) ? $action : null;
    }

    private function enforceRbac(?User $user, array $toolDefinition): void
    {
        if (! $user) {
            throw UnauthorizedException::forPermissions(['assistant.tools.' . ($toolDefinition['controller'] ?? 'unknown')]);
        }

        foreach ($toolDefinition['permissions'] ?? [] as $permission) {
            if (! $user->can($permission)) {
                throw UnauthorizedException::forPermissions([$permission]);
            }
        }

        foreach ($toolDefinition['roles'] ?? [] as $role) {
            if (! $user->hasRole($role)) {
                throw UnauthorizedException::forPermissions(["role:{$role}"]);
            }
        }

        if (isset($toolDefinition['rbac'])) {
            $modelOrClass = $toolDefinition['rbac']['model'];
            $action = $toolDefinition['rbac']['action'];
            $checkAction = $this->classLevelRbacAction($action, $modelOrClass);

            if ($checkAction && ! $user->can($checkAction, $modelOrClass)) {
                throw UnauthorizedException::forPermissions(["{$checkAction} {$modelOrClass}"]);
            }
        }

        $controllerClass = $toolDefinition['controller'];
        $method = $toolDefinition['method'];

        $modelClass = $this->guessModelClass($controllerClass);
        $action = $this->guessActionFromMethod($method);

        if ($modelClass && $action) {
            $checkAction = $this->classLevelRbacAction($action, $modelClass);

            if ($checkAction && ! $user->can($checkAction, $modelClass)) {
                throw UnauthorizedException::forPermissions(["{$checkAction} {$modelClass}"]);
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
            'preview' => 'viewAny',
            'previewSegment' => 'viewAny',
            'dashboard' => 'viewAny',
            'dashboardWidgets' => 'viewAny',
            'run' => 'viewAny',
            'showApi' => 'view',
            'getBalance' => 'view',
            'permissions' => 'view',
            'deliveries' => 'view',
            'unread' => 'viewAny',
            'upcoming' => 'viewAny',
            'ledger' => 'viewAny',
        ];

        return $map[$method] ?? null;
    }

    private function extractRecordUrl(array $content, string $tool): ?string
    {
        $routeMap = [
            'contacts.search' => '/contacts',
            'contacts.get' => '/contacts',
            'contacts.timeline' => '/contacts',
            'accounts.search' => '/accounts',
            'accounts.get' => '/accounts',
            'deals.search' => '/deals',
            'deals.get' => '/deals',
            'deals.move_stage' => '/deals',
            'deals.create' => '/deals',
            'tickets.search' => '/support/tickets',
            'tickets.get' => '/support/tickets',
            'tickets.create' => '/support/tickets/create',
            'tickets.update_status' => '/support/tickets',
            'contracts.search' => '/contracts',
            'contracts.get_status' => '/contracts',
            'invoices.search' => '/invoices',
            'invoices.get_ledger' => '/invoices',
        ];

        $id = $content['id']
            ?? $content['deal']['id']
            ?? $content['ticket']['id']
            ?? $content['contact']['id']
            ?? $content['account']['id']
            ?? $content['contact_id']
            ?? $content['account_id']
            ?? null;
        $route = $routeMap[$tool] ?? null;

        if (! $route || ! $id) {
            return null;
        }

        return "{$route}/{$id}";
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

        if ($tool === 'comments.post') {
            $actions[] = 'Create mention notifications';
            $actions[] = 'Notify mentioned users';
        }

        if ($tool === 'tasks.create') {
            $actions[] = 'Create activity reminder';
            $actions[] = 'Notify assigned user when due';
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

    private function logToolCall(string $tool, string $tier, ?string $userId, ?string $sessionId, int $latencyMs, bool $success, ?string $error, array $input, array $output = []): void
    {
        try {
            $conversation = null;

            if ($userId && $sessionId) {
                $conversation = AssistantConversation::firstOrCreate(
                    ['session_id' => $sessionId],
                    [
                        'user_id' => $userId,
                        'started_at' => now(),
                        'tool_calls_count' => 0,
                    ]
                );

                $conversation->increment('tool_calls_count');

                if ($tier === 'write-significant') {
                    $conversation->increment($success ? 'write_significant_confirmed' : 'write_significant_cancelled');
                }
            }

            AssistantToolCall::create([
                'conversation_id' => $conversation?->id,
                'tool_name' => $tool,
                'input_json' => $input,
                'output_json' => $output,
                'tier' => $tier,
                'success' => $success,
                'error_message' => $error,
                'latency_ms' => $latencyMs ?: null,
                'created_at' => now(),
            ]);

            if ($userId) {
                activity()
                    ->causedBy(User::find($userId))
                    ->withProperties([
                        'actor_type' => 'assistant',
                        'tool' => $tool,
                        'tier' => $tier,
                        'session_id' => $sessionId,
                        'success' => $success,
                        'error' => $error,
                        'input' => $input,
                        'output' => $output,
                    ])
                    ->log($success ? "Assistant tool call '{$tool}' succeeded" : "Assistant tool call '{$tool}' failed");
            }

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
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Assistant tool audit logging failed', [
                'tool' => $tool,
                'error' => $e->getMessage(),
                'actor_type' => 'assistant',
            ]);
        }
    }
}

