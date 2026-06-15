<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    const EDIT_LOCK_MINUTES = 15;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'body',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }

    public function mentions()
    {
        return $this->hasMany(CommentMention::class);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function canEdit(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        return $this->created_at->addMinutes(self::EDIT_LOCK_MINUTES)->isFuture();
    }

    public function canDelete(?User $user = null): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isDeleted()) {
            return false;
        }

        $isAuthor = $this->user_id === $user->id;
        $isManager = $user->hasRole('admin') || $user->hasRole('manager');

        return $isAuthor || $isManager;
    }

    protected static function booted(): void
    {
        static::created(function ($comment) {
            activity()
                ->inLog('comments')
                ->on($comment->commentable)
                ->by($comment->user)
                ->withProperties(['action' => 'created', 'excerpt' => str()->limit($comment->body, 100)])
                ->log('Comment added');
        });

        static::updated(function ($comment) {
            activity()
                ->inLog('comments')
                ->on($comment->commentable)
                ->by($comment->user)
                ->withProperties(['action' => 'updated', 'excerpt' => str()->limit($comment->body, 100)])
                ->log('Comment updated');
        });
    }
}