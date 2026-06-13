<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory, HasUlids;

    public const TYPE_FULL_TIME = 'full_time';

    public const TYPE_PART_TIME = 'part_time';

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_INTERN = 'intern';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_LEAVE = 'on_leave';

    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'user_id',
        'employee_number',
        'department',
        'job_title',
        'employment_type',
        'start_date',
        'end_date',
        'employment_status',
        'reporting_manager_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    public static function generateEmployeeNumber(): string
    {
        $year = now()->format('y');
        $last = self::whereYear('created_at', now()->year)
            ->orderByDesc('employee_number')
            ->first();

        $sequence = $last ? (int) substr($last->employee_number, -4) + 1 : 1;

        return "EMP-{$year}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
