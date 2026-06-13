<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCampaignStep implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $recipientId) {}

    public function handle(): void
    {
        $recipient = CampaignRecipient::with(['contact', 'step.emailTemplate'])->find($this->recipientId);

        if (! $recipient || ! $recipient->isPending()) {
            return;
        }

        $recipient->update(['status' => 'sending', 'sent_at' => now()]);

        try {
            $this->sendEmail($recipient);
            $recipient->update(['status' => 'sent']);
        } catch (\Exception $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function sendEmail(CampaignRecipient $recipient): void
    {
        // Email implementation using Laravel's Mail facade
        // Would inject tracking pixel and rewrite URLs with tracking tokens
        $template = $recipient->step->emailTemplate;

        if ($template) {
            $html = str_replace(
                '<a href',
                '<a href="'.route('tracking.redirect', $recipient->tracking_token).'" data-original-href',
                $template->html_content ?? ''
            );

            // In real implementation: Mail::to($recipient->contact->email)->send(...);
        }
    }
}
