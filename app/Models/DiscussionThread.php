<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionThread extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'discussion_board_id',
        'user_id',
        'title',
        'body',
        'is_pinned',
        'is_resolved',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(DiscussionBoard::class, 'discussion_board_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(DiscussionReply::class, 'thread_id')
            ->orderBy('created_at', 'asc');
    }

    public function resolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
        ]);
    }
}