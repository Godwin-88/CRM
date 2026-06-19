<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssistantLowConfidenceRoute extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'session_id',
        'user_query',
        'resolved_intent',
        'confidence_score',
        'flagged',
        'created_at',
    ];

    protected $casts = [
        'confidence_score' => 'float',
        'flagged' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected $table = 'assistant_low_confidence_routes';
    public $timestamps = false;

    public static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->id) {
                $model->id = (string) Str::ulid();
            }
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }
}
