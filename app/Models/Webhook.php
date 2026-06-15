<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'url',
        'events',
        'signing_secret',
        'created_by',
        'is_active',
        'auto_pause',
        'consecutive_failures',
        'last_failure_at',
        'last_success_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'auto_pause' => 'boolean',
        'last_failure_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
