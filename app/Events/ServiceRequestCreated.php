<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest
    ) {}
}
