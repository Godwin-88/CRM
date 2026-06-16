<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocRequest extends Model
{
    use HasUlids;

    protected $fillable = [
        'screen_identifier',
        'user_id',
        'comment',
        'request_count',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function incrementRequestCount(): void
    {
        $this->increment('request_count');
    }

    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }
}