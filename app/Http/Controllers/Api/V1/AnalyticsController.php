<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(protected ForecastService $forecastService) {}

    public function forecast(Request $request): JsonResponse
    {
        $filters = $request->only(['owner_id', 'team_id', 'pipeline_id', 'close_from', 'close_to']);

        $data = $this->forecastService->getRevenueForecast($filters);

        return response()->json([
            'forecast' => $data,
            'time_bucketed' => $this->forecastService->getTimeBucketedForecast($filters),
            'historical_win_rates' => $this->forecastService->getHistoricalWinRates(),
        ]);
    }

    public function winLossAnalysis(Request $request): JsonResponse
    {
        $filters = $request->only(['owner_id', 'team_id', 'pipeline_id', 'close_from', 'close_to']);

        return response()->json($this->forecastService->getWinLossAnalysis($filters));
    }
}
