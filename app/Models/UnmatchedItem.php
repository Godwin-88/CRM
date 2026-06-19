<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnmatchedItem extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'source_type',
        'external_id',
        'raw_payload',
        'matched_contact_id',
        'assigned_to',
        'status',
        'resolution_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'matched_contact_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getChannelAttribute(): ?\App\Models\InteractionChannel
    {
        return \App\Models\InteractionChannel::where('name', $this->source_type)->first();
    }

    public function getSubjectAttribute(): string
    {
        return ucfirst($this->source_type).' from '.$this->external_id;
    }
}
