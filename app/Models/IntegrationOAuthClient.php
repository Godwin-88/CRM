<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationOAuthClient extends Model
{
    protected $fillable = [
        'name',
        'redirect_uris',
        'grant_types',
        'client_id',
        'client_secret',
        'is_personal',
        'user_id',
        'scopes',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'grant_types' => 'array',
        'scopes' => 'array',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
