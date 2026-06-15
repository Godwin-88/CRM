<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DiscussionBoard extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'boardable_type',
        'boardable_id',
        'title',
    ];

    public function boardable(): MorphTo
    {
        return $this->morphTo();
    }

    public function threads()
    {
        return $this->hasMany(DiscussionThread::class);
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('boardable_type', $model->getMorphClass())
            ->where('boardable_id', $model->id);
    }
}