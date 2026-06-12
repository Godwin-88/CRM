<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DripSequence extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'trigger',
        'trigger_conditions',
        'status',
        'created_by',
        'allow_re_enrolment',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DripSequenceStep::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(DripEnrolment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}