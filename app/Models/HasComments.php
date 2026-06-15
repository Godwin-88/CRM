<?php

namespace App\Models;

trait HasComments
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('created_at', 'asc');
    }

    public function commentCount(): int
    {
        return $this->comments()->count();
    }
}