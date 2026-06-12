<?php

namespace App\Jobs;

use App\Models\AutomationJob;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class ProcessAutomationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $automationJobId) {}

    public function handle(): void
    {
        $job = AutomationJob::with(['action.deal', 'action.automation.stage'])->find($this->automationJobId);

        if (!$job || !$job->isPending()) {
            return;
        }

        $job->update(['status' => 'processing']);

        try {
            match ($job->action->action_type) {
                'create_activity' => $this->createActivity($job),
                'send_email' => $this->sendEmail($job),
                'send_webhook' => $this->sendWebhook($job),
            };

            $job->update(['status' => 'completed', 'processed_at' => now()]);
        } catch (\Exception $e) {
            $job->increment('retry_count');

            if ($job->retry_count >= 3) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            } else {
                $job->update(['error_message' => $e->getMessage()]);
                $this->release($this->getDelayForRetry($job->retry_count));
            }
        }
    }

    protected function createActivity(AutomationJob $job): void
    {
        $assignedTo = $job->action->assigned_to ?? $job->action->deal->owner_id;

        $activity = Activity::create([
            'subject' => $job->action->deal->stage . ' Stage Reminder',
            'type' => 'task',
            'due_at' => now()->addDays(1),
            'assigned_to' => $assignedTo,
            'deal_id' => $job->action->deal->id,
            'contact_id' => $job->action->deal->contact_id,
            'account_id' => $job->action->deal->account_id,
        ]);
    }

    protected function sendEmail(AutomationJob $job): void
    {
        // Email implementation would use Laravel's Mail facade
        $emailTo = $job->action->email_to ?? $job->action->deal->owner->email;

        \Mail::to($emailTo)->send(
            new \App\Mail\DealStageNotification($job->action->deal, $job->action->deal->stage)
        );
    }

    protected function sendWebhook(AutomationJob $job): void
    {
        Http::post($job->action->webhook_url, [
            'deal_id' => $job->action->deal->id,
            'stage' => $job->action->deal->stage,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function getDelayForRetry(int $retryCount): int
    {
        return match($retryCount) {
            1 => 60,
            2 => 300,
            default => 900,
        };
    }
}