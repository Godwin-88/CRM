<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallRecording extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'interaction_id',
        'provider_call_sid',
        'recording_url',
        'storage_path',
        'duration_seconds',
    ];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }
}
