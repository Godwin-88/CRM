<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignStep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DispatchCampaign implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::with(['segment.contacts', 'steps.emailTemplate', 'abTest'])->find($this->campaignId);

        if (! $campaign || ! $campaign->isScheduled()) {
            return;
        }

        $campaign->update(['status' => 'sending', 'started_at' => now()]);

        $contacts = $campaign->segment->contacts ?? collect();
        $throttleDelay = (3600 / $campaign->throttle_emails_per_hour) * 1000; // milliseconds between sends

        foreach ($contacts as $contact) {
            $this->createRecipients($campaign, $contact);
            $this->dispatchSteps($campaign, $contact);
        }

        $campaign->update(['status' => 'sent', 'completed_at' => now()]);
    }

    protected function createRecipients(Campaign $campaign, $contact): void
    {
        foreach ($campaign->steps as $step) {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'campaign_step_id' => $step->id,
                'contact_id' => $contact->id,
                'status' => 'pending',
                'tracking_token' => Str::ulid(),
                'redirect_url' => $step->emailTemplate->html_content ?? '#',
            ]);
        }
    }

    protected function dispatchSteps(Campaign $campaign, $contact): void
    {
        $recipients = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('contact_id', $contact->id)
            ->orderBy('campaign_step_id')
            ->get();

        foreach ($recipients as $recipient) {
            ProcessCampaignStep::dispatch($recipient->id)
                ->delay($this->getDelay($recipient->step));
        }
    }

    protected function getDelay(CampaignStep $step): int
    {
        if ($step->delay_type === 'immediately') {
            return 0;
        }

        $multiplier = $step->delay_type === 'n_hours' ? 3600 : 86400; // seconds

        return $step->delay_value * $multiplier;
    }
}
