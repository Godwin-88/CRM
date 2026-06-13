<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRating extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'ticket_ratings';

    protected $fillable = [
        'ticket_id',
        'score',
        'comment',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('score', '<=', 2);
    }
}
