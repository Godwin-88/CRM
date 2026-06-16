<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocChecklist extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'checklist_item_key',
        'completed_at',
        'dismissed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isDismissed(): bool
    {
        return $this->dismissed_at !== null;
    }

    public function markCompleted(): void
    {
        $this->update(['completed_at' => now(), 'dismissed_at' => null]);
    }

    public function markDismissed(): void
    {
        $this->update(['dismissed_at' => now(), 'completed_at' => null]);
    }
}