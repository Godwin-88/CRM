<?php

namespace App\Jobs;

use App\Models\Survey;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSurveyInvitation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Survey $survey,
        public Contact $contact
    ) {}

    public function handle(): void
    {
        $token = encrypt($this->contact->id);
        $url = url('/survey/' . $this->survey->id . '/' . $token);

        if ($this->survey->channel === 'email') {
            \Mail::to($this->contact->email)->send(new \App\Mail\SurveyInvitationMail(
                $this->survey,
                $url
            ));
        } elseif ($this->survey->channel === 'sms') {
            app(\App\Services\SmsService::class)->send(
                $this->contact->phone,
                "Please complete our survey: {$url}"
            );
        }

        \App\Models\Notification::create([
            'user_id' => $this->contact->id,
            'type' => 'survey_invitation',
            'data' => [
                'survey_id' => $this->survey->id,
                'survey_name' => $this->survey->name,
                'url' => $url,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
