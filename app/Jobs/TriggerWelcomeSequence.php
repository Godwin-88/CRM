<?php

namespace App\Jobs;

use App\Models\DripEnrolment;
use App\Models\DripSequence;
use App\Models\OnboardingRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TriggerWelcomeSequence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OnboardingRecord $record) {}

    public function handle(): void
    {
        $sequence = DripSequence::where('trigger', 'welcome')
            ->where('status', 'active')
            ->first();

        if (! $sequence) {
            return;
        }

        $contactId = $this->record->contact_id;
        if (! $contactId) {
            return;
        }

        $exists = DripEnrolment::where('drip_sequence_id', $sequence->id)
            ->where('contact_id', $contactId)
            ->where('status', 'active')
            ->exists();

        if (! $exists) {
            DripEnrolment::create([
                'drip_sequence_id' => $sequence->id,
                'contact_id' => $contactId,
                'status' => 'active',
                'enroled_at' => now(),
            ]);
        }
    }
}
