<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestLink extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'service_request_id',
        'linked_service_request_id',
        'link_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function linkedServiceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'linked_service_request_id');
    }
}
