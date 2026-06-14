<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrRequest extends Model
{
    protected $fillable = [
        'type',
        'contact_id',
        'requested_by',
        'status',
        'handled_by',
        'completed_at',
        'blocking_reason',
        'justification',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
