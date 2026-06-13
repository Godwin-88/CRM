<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ComplianceAnalyticsService
{
    public function getAuditTrail(array $filters = []): array
    {
        $query = Activity::query()->with('causer');

        if (isset($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }
        if (isset($filters['event_type'])) {
            $query->where('event', $filters['event_type']);
        }
        if (isset($filters['model_type'])) {
            $query->where('subject_type', $filters['model_type']);
        }
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['ip_address'])) {
            $query->where('properties->ip_address', $filters['ip_address']);
        }

        $perPage = $filters['per_page'] ?? 50;

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAuditStats(array $filters = []): array
    {
        $query = Activity::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $eventBreakdown = $query->select('event', DB::raw('count(*) as count'))
            ->groupBy('event')
            ->get();

        $topUsers = $query->select('causer_id', DB::raw('count(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->causer_id,
                'name' => User::find($item->causer_id)?->name ?? 'Unknown',
                'count' => $item->count,
            ]);

        $dailyActivity = $query->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as date"),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_events' => $query->count(),
            'event_breakdown' => $eventBreakdown,
            'top_users' => $topUsers,
            'daily_activity' => $dailyActivity,
        ];
    }

    public function detectAnomalies(array $filters = []): array
    {
        $now = Carbon::now();
        $anomalies = [];

        $highActivityUsers = Activity::where('created_at', '>=', $now->copy()->subHour())
            ->select('causer_id', DB::raw('count(*) as count'))
            ->groupBy('causer_id')
            ->having('count', '>', 500)
            ->get();

        foreach ($highActivityUsers as $user) {
            $anomalies[] = [
                'user_id' => $user->causer_id,
                'type' => 'high_activity',
                'description' => "Generated {$user->count} audit events in the last hour",
                'detected_at' => $now->toIso8601String(),
                'severity' => 'warning',
            ];
        }

        $bulkExports = Activity::where('event', 'exported')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->where('properties->record_count', '>', 1000)
            ->get();

        foreach ($bulkExports as $export) {
            $anomalies[] = [
                'user_id' => $export->causer_id,
                'type' => 'bulk_export',
                'description' => "Exported {$export->properties['record_count']} records",
                'detected_at' => $now->toIso8601String(),
                'severity' => 'critical',
            ];
        }

        $failedLogins = DB::table('audit_login_attempts')
            ->where('created_at', '>=', $now->copy()->subMinutes(10))
            ->select('user_id', DB::raw('count(*) as attempt_count'))
            ->groupBy('user_id')
            ->having('attempt_count', '>', 5)
            ->get();

        foreach ($failedLogins as $login) {
            $anomalies[] = [
                'user_id' => $login->user_id,
                'type' => 'failed_login_attempts',
                'description' => "{$login->attempt_count} failed login attempts in 10 minutes",
                'detected_at' => $now->toIso8601String(),
                'severity' => 'warning',
            ];
        }

        return $anomalies;
    }

    public function getRetentionMonths(): int
    {
        return (int) config('analytics.audit_retention_months', 84);
    }
}