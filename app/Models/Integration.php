<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'type',
        'provider',
        'config',
        'is_active',
        'created_by',
        'category',
        'logo',
        'description',
        'connection_status',
        'last_active_at',
        'webhook_events',
        'rate_limit_key',
        'api_key',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'webhook_events' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $appends = [
        'masked_api_key',
    ];

    public function getMaskedApiKeyAttribute(): ?string
    {
        if (! $this->api_key) {
            return null;
        }

        return '••••'.substr(decrypt($this->api_key), -4);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unmatchedItems(): HasMany
    {
        return $this->hasMany(UnmatchedItem::class);
    }
}
