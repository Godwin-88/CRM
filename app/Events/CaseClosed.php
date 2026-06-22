<?php

namespace App\Events;

use App\Models\CaseRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CaseRecord $caseRecord
    ) {}
}
