<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealAutomation;
use App\Models\AutomationJob;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\DB;

class DealService
{
    public function moveDealToStage(Deal $deal, string $newStage): Deal
    {
        return DB::transaction(function () use ($deal, $newStage) {
            $oldStage = $deal->stage;
            $deal->update(['stage' => $newStage]);

            $stage = PipelineStage::where('pipeline_id', $deal->pipeline_id)
                ->where('name', $newStage)
                ->first();

            if ($stage) {
                $deal->update(['probability' => $stage->probability]);
            }

            return $deal->fresh();
        });
    }

    public function scheduleAutomationsForDeal(Deal $deal, ?string $oldStage = null): void
    {
        if ($deal->exclude_from_automations) {
            return;
        }

        $stage = PipelineStage::where('pipeline_id', $deal->pipeline_id)
            ->where('name', $deal->stage)
            ->first();

        if (!$stage) {
            return;
        }

        $automations = DealAutomation::where('pipeline_stage_id', $stage->id)
            ->where('is_active', true)
            ->with('actions')
            ->get();

foreach ($automations as $automation) {
             foreach ($automation->actions as $action) {
                 $scheduledAt = match($action->delay_type) {
                     'immediate' => now(),
                     'one_hour' => now()->addHour(),
                     'one_day' => now()->addDay(),
                     'n_business_days' => now()->addDays(($action->delay_days ?? 1) * 2),
                     default => now(),
                 };

                AutomationJob::create([
                    'deal_automation_id' => $automation->id,
                    'deal_id' => $deal->id,
                    'automation_action_id' => $action->id,
                    'scheduled_at' => $scheduledAt,
                ]);
            }
        }
    }

    public function calculateForecast(array $filters = []): array
    {
        $query = Deal::query()->whereNotIn('stage', ['closed_won', 'closed_lost']);

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }
        if (isset($filters['close_from'])) {
            $query->whereDate('expected_close_date', '>=', $filters['close_from']);
        }
        if (isset($filters['close_to'])) {
            $query->whereDate('expected_close_date', '<=', $filters['close_to']);
        }

        $totalValue = $query->sum('value');
        $totalWeighted = $query->get()->sum(fn($d) => $d->getWeightedValue());

        return [
            'total_value' => $totalValue,
            'total_weighted_value' => $totalWeighted,
            'deal_count' => $query->count(),
        ];
    }
}