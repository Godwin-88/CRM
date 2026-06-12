<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingActivity extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'record_id',
        'template_step_id',
        'name',
        'description',
        'assigned_role',
        'assigned_to',
        'due_date',
        'status',
        'completion_note',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(OnboardingRecord::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
