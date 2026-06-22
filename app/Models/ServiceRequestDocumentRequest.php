<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestDocumentRequest extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'service_request_id',
        'title',
        'description',
        'required_by',
        'status',
        'requested_by_id',
        'fulfilled_at',
        'metadata',
    ];

    protected $casts = [
        'required_by' => 'datetime',
        'fulfilled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }
}
