<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'segment_id',
        'name',
        'type',
        'question_text',
        'follow_up_question',
        'status',
        'channel',
        'contact_ids',
        'created_by',
        'sent_at',
        'closed_at',
    ];

    protected $casts = [
        'contact_ids' => 'array',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
