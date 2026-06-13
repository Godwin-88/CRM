<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Notification;
use App\Models\Survey;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSurveySms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Survey $survey,
        public Contact $contact
    ) {}

    public function handle(): void
    {
        $token = encrypt($this->contact->id);
        $url = url('/survey/'.$this->survey->id.'/'.$token);

        app(SmsService::class)->send(
            $this->contact->phone,
            "Complete our survey: {$url}"
        );

        Notification::create([
            'user_id' => $this->contact->id,
            'type' => 'survey_invitation_sms',
            'data' => [
                'survey_id' => $this->survey->id,
                'url' => $url,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
