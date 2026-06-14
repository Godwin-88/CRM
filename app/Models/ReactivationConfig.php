<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReactivationConfig extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'criteria',
        'actions',
        'contact_type',
        'inactivity_days_threshold',
        'drip_sequence_id',
        'dormant_tag',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'criteria' => 'array',
        'actions' => 'array',
        'inactivity_days_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dripSequence(): BelongsTo
    {
        return $this->belongsTo(DripSequence::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ReactivationContact::class, 'config_id');
    }
}
