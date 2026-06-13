<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketForm extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ticket_category_id',
        'name',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }
}
