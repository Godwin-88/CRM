<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest
    ) {}
}
