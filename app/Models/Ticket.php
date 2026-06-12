<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'subject',
        'description',
        'contact_id',
        'account_id',
        'priority',
        'status',
        'category_id',
        'assigned_to',
        'sla_breached_at',
        'resolved_at',
    ];

    protected $casts = [
        'sla_breached_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}