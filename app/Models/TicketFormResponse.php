<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFormResponse extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'ticket_form_responses';

    protected $fillable = [
        'ticket_id',
        'ticket_form_id',
        'ticket_category_id',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(TicketForm::class, 'ticket_form_id');
    }
}