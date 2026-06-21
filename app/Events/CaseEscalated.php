<?php

namespace App\Events;

use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseEscalated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CaseRecord $caseRecord,
        public User $escalatedBy,
        public string $reason
    ) {}
}
