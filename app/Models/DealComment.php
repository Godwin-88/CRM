<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealComment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'deal_id',
        'user_id',
        'body',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(DealCommentMention::class);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function canEdit(int $minutes = 15): bool
    {
        if ($this->isDeleted()) {
            return false;
        }
        return $this->created_at->addMinutes($minutes)->isFuture();
    }
}