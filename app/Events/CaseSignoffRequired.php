<?php

namespace App\Events;

use App\Models\CaseRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseSignoffRequired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CaseRecord $caseRecord
    ) {}
}
