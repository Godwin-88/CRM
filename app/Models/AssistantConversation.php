<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssistantConversation extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'session_id',
        'model_provider',
        'model_name',
        'started_at',
        'ended_at',
        'tool_calls_count',
        'write_significant_confirmed',
        'write_significant_cancelled',
        'feedback_positive',
        'feedback_negative',
        'feedback_comment',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toolCalls()
    {
        return $this->hasMany(AssistantToolCall::class, 'conversation_id');
    }

    public static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->id) {
                $model->id = (string) Str::ulid();
            }
        });
    }
}
