<?php

namespace App\Http\Controllers\Api\V1;

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
        $filters = $request->only(['date_from', 'date_to']);

        return response()->json($this->complianceService->detectAnomalies($filters));
    }
}
