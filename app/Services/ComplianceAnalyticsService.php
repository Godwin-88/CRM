<?php

namespace App\Services;

use App\Models\AuditAnomaly;
use App\Models\AuditRetentionSetting;
use App\Models\SecurityEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ComplianceAnalyticsService
{
    public function getAuditTrail(array $filters = []): array
    {
        $query = Activity::query()->with('causer');

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('causer_id', $filters['user_id']);
        }
        if (isset($filters['event_type']) && $filters['event_type'] !== '') {
            $query->where('event', $filters['event_type']);
        }
        if (isset($filters['model_type']) && $filters['model_type'] !== '') {
            $query->where('subject_type', $filters['model_type']);
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['ip_address']) && $filters['ip_address'] !== '') {
            $query->where('properties->ip_address', $filters['ip_address']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 50), 50);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->toArray();
    }

    public function getAuditStats(array $filters = []): array
    {
        $query = Activity::query();

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $eventBreakdown = $query->clone()
            ->select('event', DB::raw('count(*) as count'))
            ->groupBy('event')
            ->get();

        $topUsers = $query->clone()
            ->select('causer_id', DB::raw('count(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'user_id' => $item->causer_id,
                'name' => User::find($item->causer_id)?->name ?? 'Unknown',
                'count' => (int) $item->count,
            ]);

        $dailyActivity = $query->clone()
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as date"),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_events' => (int) $query->count(),
            'event_breakdown' => $eventBreakdown->map(fn ($item) => [
                'event' => $item->event,
                'count' => (int) $item->count,
            ])->toArray(),
            'top_users' => $topUsers->toArray(),
            'daily_activity' => $dailyActivity->map(fn ($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
            ])->toArray(),
        ];
    }

    public function detectAnomalies(array $filters = []): array
    {
        $now = Carbon::now();
        $anomalies = [];

        $highActivityUsers = Activity::where('created_at', '>=', $now->copy()->subHour())
            ->select('causer_id', DB::raw('count(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->having('count', '>', 500)
            ->get();

        foreach ($highActivityUsers as $user) {
            $anomalies[] = $this->recordAnomaly($user->causer_id, 'high_activity', "Generated {$user->count} audit events in the last hour", ['count' => $user->count], 'warning', $now);
        }

        $bulkExports = Activity::where('event', 'exported')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->where('properties->record_count', '>', 1000)
            ->get();

        foreach ($bulkExports as $export) {
            $recordCount = (int) ($export->properties['record_count'] ?? 0);
            $anomalies[] = $this->recordAnomaly($export->causer_id, 'bulk_export', "Exported {$recordCount} contact records", ['record_count' => $recordCount], 'critical', $now);
        }

        $newIpLogins = SecurityEvent::where('event_type', 'login_success')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->whereNotNull('user_id')
            ->whereNotNull('ip_address')
            ->get();

        foreach ($newIpLogins as $login) {
            $seenBefore = SecurityEvent::where('user_id', $login->user_id)
                ->where('event_type', 'login_success')
                ->where('ip_address', $login->ip_address)
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->where('created_at', '<', $login->created_at)
                ->exists();

            if (! $seenBefore) {
                $anomalies[] = $this->recordAnomaly($login->user_id, 'new_ip_login', "Login from new IP address {$login->ip_address}", ['ip_address' => $login->ip_address], 'warning', $login->created_at);
            }
        }

        $failedLogins = SecurityEvent::where('event_type', 'login_failure')
            ->where('created_at', '>=', $now->copy()->subMinutes(10))
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as attempt_count'))
            ->groupBy('user_id')
            ->having('attempt_count', '>', 5)
            ->get();

        foreach ($failedLogins as $login) {
            $anomalies[] = $this->recordAnomaly($login->user_id, 'failed_login_attempts', "{$login->attempt_count} failed login attempts in 10 minutes", ['attempt_count' => $login->attempt_count], 'warning', $now);
        }

        $results = array_values($anomalies);

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $results = array_values(array_filter($results, fn ($anomaly) => $anomaly['detected_at'] && $anomaly['detected_at'] >= $filters['date_from']));
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $results = array_values(array_filter($results, fn ($anomaly) => $anomaly['detected_at'] && $anomaly['detected_at'] <= $filters['date_to']));
        }
        if (array_key_exists('acknowledged', $filters) && $filters['acknowledged'] !== '') {
            $wanted = filter_var($filters['acknowledged'], FILTER_VALIDATE_BOOLEAN);
            $results = array_values(array_filter($results, fn ($anomaly) => ($anomaly['acknowledged_at'] !== null) === $wanted));
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $results = array_values(array_filter($results, fn ($anomaly) => $anomaly['user_id'] === $filters['user_id']));
        }
        if (isset($filters['event_type']) && $filters['event_type'] !== '') {
            $results = array_values(array_filter($results, fn ($anomaly) => $anomaly['event_type'] === $filters['event_type']));
        }
        if (isset($filters['ip_address']) && $filters['ip_address'] !== '') {
            $results = array_values(array_filter($results, fn ($anomaly) => ($anomaly['metadata']['ip_address'] ?? null) === $filters['ip_address']));
        }
        if (isset($filters['per_page']) && $filters['per_page'] !== '') {
            $results = array_slice($results, 0, min((int) $filters['per_page'], 100));
        }

        return $results;
    }

    public function acknowledgeAnomaly(string $anomalyId, User $user, ?string $note = null): AuditAnomaly
    {
        $anomaly = AuditAnomaly::findOrFail($anomalyId);

        $anomaly->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'acknowledged_note' => $note,
        ]);

        return $anomaly;
    }

    public function getRetentionMonths(): int
    {
        $months = AuditRetentionSetting::query()
            ->where('key', 'audit_retention_months')
            ->value('value');

        return (int) ($months ?? 84);
    }

    public function retentionSettings(): array
    {
        return [
            'audit_retention_months' => $this->getRetentionMonths(),
        ];
    }

    protected function recordAnomaly(?string $userId, string $type, string $description, array $metadata, string $severity, Carbon $detectedAt): array
    {
        $anomaly = AuditAnomaly::query()
            ->where('user_id', $userId)
            ->where('event_type', $type)
            ->where('description', $description)
            ->where('detected_at', '>=', $detectedAt->copy()->subHour())
            ->first();

        if (! $anomaly) {
            $anomaly = AuditAnomaly::create([
                'user_id' => $userId,
                'event_type' => $type,
                'description' => $description,
                'metadata' => [...$metadata, 'severity' => $severity],
                'detected_at' => $detectedAt,
            ]);
        }

        return [
            'id' => $anomaly->id,
            'user_id' => $anomaly->user_id,
            'event_type' => $anomaly->event_type,
            'description' => $anomaly->description,
            'detected_at' => $anomaly->detected_at?->toIso8601String(),
            'severity' => $anomaly->metadata['severity'] ?? $severity,
            'acknowledged_at' => $anomaly->acknowledged_at?->toIso8601String(),
        ];
    }
}
