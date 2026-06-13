<?php

namespace App\Jobs;

use App\Mail\LoyaltyCommunicationMail;
use App\Models\LoyaltyCommunicationLog;
use App\Models\LoyaltyCommunicationPreference;
use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLoyaltyCommunication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $template,
        public $contact,
        public ?array $context = null
    ) {}

    public function handle(): void
    {
        $channel = $this->template->channel;

        // Respect opt-out preferences
        $pref = LoyaltyCommunicationPreference::where('contact_id', $this->contact->id)->first();
        if ($pref && $pref->loyalty_opt_out) {
            $this->logDelivery('skipped');

            return;
        }

        // Check contact general marketing opt-out
        if (method_exists($this->contact, 'marketing_opt_out') && $this->contact->marketing_opt_out) {
            $this->logDelivery('skipped');

            return;
        }

        // Render template with variables
        $content = $this->renderTemplate();

        $log = LoyaltyCommunicationLog::create([
            'template_id' => $this->template->id,
            'contact_id' => $this->contact->id,
            'channel' => $channel,
            'status' => 'queued',
            'payload' => ['content' => $content],
        ]);

        try {
            switch ($channel) {
                case 'email':
                    \Mail::to($this->contact->email)->send(new LoyaltyCommunicationMail($content, $this->template));
                    break;
                case 'sms':
                    // Use Twilio/SMS provider
                    app(SmsService::class)->send($this->contact->phone, $content);
                    break;
                case 'inapp':
                    Notification::create([
                        'user_id' => $this->contact->id,
                        'type' => 'loyalty_communication',
                        'data' => ['template_id' => $this->template->id, 'content' => $content],
                    ]);
                    break;
            }

            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Exception $e) {
            $log->increment('retry_count');
            $log->update([
                'status' => $log->retry_count >= 3 ? 'failed' : 'queued',
                'error_message' => $e->getMessage(),
            ]);

            if ($log->retry_count < 3) {
                self::dispatch($this->template, $this->contact, $this->context)
                    ->delay(now()->addMinutes(5));
            }
        }
    }

    private function renderTemplate(): string
    {
        $content = $this->template->body;
        $contact = $this->contact;

        // Replace template variables
        $variables = [
            '{{contact_name}}' => $contact->first_name.' '.$contact->last_name,
            '{{current_tier}}' => $contact->loyalty_tier ?? 'Bronze',
            '{{program_name}}' => $this->template->program?->name ?? 'Loyalty Program',
            '{{points_balance}}' => method_exists($contact, 'getLoyaltyPointsBalance') ? $contact->getLoyaltyPointsBalance() : 0,
            '{{points_earned}}' => $this->context['points_earned'] ?? 0,
            '{{next_tier}}' => $this->getNextTierName($contact),
            '{{points_to_next_tier}}' => $this->getPointsToNextTier($contact),
            '{{expiry_date}}' => $this->getExpiryDate($contact),
        ];

        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    private function getNextTierName($contact): string
    {
        $currentTier = $contact->loyalty_tier ?? 'bronze';
        $tiers = ['bronze' => 'Silver', 'silver' => 'Gold', 'gold' => 'Platinum', 'platinum' => 'Max Tier'];

        return $tiers[$currentTier] ?? 'Unknown';
    }

    private function getPointsToNextTier($contact): int
    {
        $thresholds = ['bronze' => 0, 'silver' => 1000, 'gold' => 5000, 'platinum' => 10000];
        $currentTier = $contact->loyalty_tier ?? 'bronze';
        $nextTier = $this->getNextTierName($contact);
        $balance = method_exists($contact, 'getLoyaltyPointsBalance') ? $contact->getLoyaltyPointsBalance() : 0;

        return max(0, ($thresholds[$nextTier] ?? 0) - $balance);
    }

    private function getExpiryDate($contact): ?string
    {
        if (method_exists($contact, 'getPointsExpiryDate')) {
            return $contact->getPointsExpiryDate()?->format('Y-m-d');
        }

        return null;
    }

    private function logDelivery(string $status): void
    {
        LoyaltyCommunicationLog::create([
            'template_id' => $this->template->id,
            'contact_id' => $this->contact->id,
            'channel' => $this->template->channel,
            'status' => $status,
            'payload' => ['reason' => 'opted_out'],
        ]);
    }
}
