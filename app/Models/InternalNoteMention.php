<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNoteMention extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'internal_note_id',
        'user_id',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(TicketInternalNote::class, 'internal_note_id')
            ->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}