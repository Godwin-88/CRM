<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditRetentionSetting;
use App\Http\Controllers\Controller;
use App\Services\ComplianceAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceAnalyticsController extends Controller
{
    public function __construct(protected ComplianceAnalyticsService $complianceService) {}

    public function auditTrail(Request $request): JsonResponse
    {
        $filters = $request->only(['user_id', 'event_type', 'model_type', 'date_from', 'date_to', 'ip_address', 'per_page']);

        return response()->json($this->complianceService->getAuditTrail($filters));
    }

    public function auditStats(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);

        return response()->json($this->complianceService->getAuditStats($filters));
    }

    public function anomalies(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id', 'event_type', 'ip_address', 'acknowledged', 'per_page']);

        return response()->json($this->complianceService->detectAnomalies($filters));
    }

    public function acknowledgeAnomaly(string $anomalyId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        return response()->json($this->complianceService->acknowledgeAnomaly($anomalyId, $request->user(), $validated['note'] ?? null));
    }

    public function retentionSettings(): JsonResponse
    {
        return response()->json($this->complianceService->retentionSettings());
    }

    public function updateRetentionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audit_retention_months' => 'required|integer|min:1|max:120',
        ]);

        AuditRetentionSetting::query()->updateOrCreate(
            ['key' => 'audit_retention_months'],
            ['value' => $validated['audit_retention_months']]
        );

        return response()->json($this->complianceService->retentionSettings());
    }
}
