<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceCatalogItemVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCatalogItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ServiceCatalogItem::class);

        $query = ServiceCatalogItem::query()
            ->with(['category', 'defaultTeam', 'slaPolicy', 'intakeFormSchema.latestVersion']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->when($request->filled('portal_visible'), fn ($q) => $q->where('portal_visible', $request->boolean('portal_visible')))
            ->when($request->filled('team_id'), fn ($q) => $q->where('default_team_id', $request->team_id))
            ->when($request->filled('sla_policy_id'), fn ($q) => $q->where('sla_policy_id', $request->sla_policy_id))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id));

        return response()->json($query->latest()->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ServiceCatalogItem::class);

        $validated = $this->validateCatalogItem($request, true);
        $validated['created_by_id'] = $request->user()?->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $catalogItem = ServiceCatalogItem::create($validated);

        $fields = $request->input('fields', $request->input('form_schema_fields', []));
        ServiceCatalogItemVersion::create([
            'service_catalog_item_id' => $catalogItem->id,
            'version_number' => 1,
            'fields' => $fields,
            'required_documents' => $validated['required_documents'] ?? [],
            'automation_config' => $validated['automation_config'] ?? [],
            'customer_instructions' => $validated['customer_instructions'] ?? null,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json($catalogItem->load('intakeFormSchema.latestVersion'), 201);
    }

    public function show(ServiceCatalogItem $serviceCatalogItem): JsonResponse
    {
        $this->authorize('view', $serviceCatalogItem);

        return response()->json($serviceCatalogItem->load([
            'category',
            'defaultTeam',
            'slaPolicy',
            'intakeFormSchema.latestVersion',
            'versions',
            'requests' => fn ($query) => $query->latest()->limit(20),
        ]));
    }

    public function update(Request $request, ServiceCatalogItem $serviceCatalogItem): JsonResponse
    {
        $this->authorize('update', $serviceCatalogItem);

        $validated = $this->validateCatalogItem($request);
        $serviceCatalogItem->update($validated);

        if ($request->has('fields') || $request->has('form_schema_fields')) {
            $latestVersion = $serviceCatalogItem->latestVersion;
            ServiceCatalogItemVersion::create([
                'service_catalog_item_id' => $serviceCatalogItem->id,
                'version_number' => ($latestVersion?->version_number ?? 0) + 1,
                'fields' => $request->input('fields', $request->input('form_schema_fields', [])),
                'required_documents' => $validated['required_documents'] ?? $serviceCatalogItem->required_documents,
                'automation_config' => $validated['automation_config'] ?? $serviceCatalogItem->automation_config,
                'customer_instructions' => $validated['customer_instructions'] ?? $serviceCatalogItem->customer_instructions,
                'is_published' => $request->boolean('publish_version', true),
                'published_at' => $request->boolean('publish_version', true) ? now() : null,
            ]);
        }

        return response()->json($serviceCatalogItem->fresh()->load('intakeFormSchema.latestVersion', 'versions'));
    }

    public function destroy(ServiceCatalogItem $serviceCatalogItem): JsonResponse
    {
        $this->authorize('delete', $serviceCatalogItem);

        $serviceCatalogItem->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        return response()->json(['message' => 'Service catalog item deactivated.']);
    }

    private function validateCatalogItem(Request $request, bool $required = false): array
    {
        $nameRule = $required ? 'required|string|max:255' : 'sometimes|string|max:255';
        $slugRule = $required
            ? 'required|string|max:255|unique:service_catalog_items,slug'
            : 'sometimes|string|max:255|unique:service_catalog_items,slug,'.$request->route('serviceCatalogItem')?->id;

        $request->merge([
            'category_id' => $request->filled('category_id') ? $request->category_id : null,
            'default_team_id' => $request->filled('default_team_id') ? $request->default_team_id : null,
            'sla_policy_id' => $request->filled('sla_policy_id') ? $request->sla_policy_id : null,
            'intake_form_schema_id' => $request->filled('intake_form_schema_id') ? $request->intake_form_schema_id : null,
        ]);

        return $request->validate([
            'name' => $nameRule,
            'slug' => $slugRule,
            'category_id' => 'nullable|exists:ticket_categories,id',
            'description' => 'nullable|string',
            'customer_instructions' => 'nullable|string',
            'default_priority' => 'nullable|in:low,medium,high,urgent',
            'default_team_id' => 'nullable|exists:teams,id',
            'default_owner_role' => 'nullable|string|max:255',
            'sla_policy_id' => 'nullable|exists:sla_definitions,id',
            'intake_form_schema_id' => 'nullable|exists:form_schemas,id',
            'required_documents' => 'nullable|array',
            'automation_config' => 'nullable|array',
            'portal_visible' => 'sometimes|boolean',
            'email_visible' => 'sometimes|boolean',
            'kiosk_visible' => 'sometimes|boolean',
            'api_visible' => 'sometimes|boolean',
            'is_agent_only' => 'sometimes|boolean',
        ]);
    }
}
