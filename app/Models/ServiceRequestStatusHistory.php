<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestStatusHistory extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'service_request_id',
        'from_status',
        'to_status',
        'transitioned_by_id',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function transitionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by_id');
    }
}
