<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketInternalNote extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'ticket_internal_notes';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'body',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(InternalNoteMention::class, 'internal_note_id');
    }

    public function isDeleted(): bool
    {
        return !is_null($this->deleted_at);
    }

    public function isLocked(): bool
    {
        if (!$this->created_at) {
            return false;
        }
        return $this->created_at->addMinutes(30)->isPast();
    }
}