<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSession extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'interaction_id',
        'visitor_token',
        'visitor_email',
        'visitor_name',
        'matched_contact_id',
        'assigned_agent_id',
        'status',
        'wait_time_seconds',
        'metadata',
        'closed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'closed_at' => 'datetime',
        'wait_time_seconds' => 'integer',
    ];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'matched_contact_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}