<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\InteractionAttachment;
use App\Models\UnmatchedItem;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Jobs\ProcessInboundEmail;

class EmailService
{
    public function sendFromTemplate(string $templateId, string $contactId, ?string $dealId = null, ?string $ticketId = null, array $variables = [], ?string $agentId = null): Interaction
    {
        $template = EmailTemplate::findOrFail($templateId);
        $contact = Contact::findOrFail($contactId);
        $agent = $agentId ? User::findOrFail($agentId) : User::first();

        $body = $this->renderTemplate($template->body, array_merge([
            'contact_name' => $contact->first_name . ' ' . $contact->last_name,
            'agent_name' => $agent->name,
            'account_name' => $contact->account?->name ?? '',
        ], $variables));

        $subject = $this->renderTemplate($template->subject, array_merge([
            'contact_name' => $contact->first_name,
        ], $variables));

        // Send email
        Mail::to($contact->email)->send(new \App\Mail\TemplateMail($subject, $body, $contact));

        // Log interaction
        $interaction = Interaction::create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'deal_id' => $dealId,
            'type' => 'email',
            'channel_id' => null,
            'direction' => 'outbound',
            'subject' => $subject,
            'body' => $body,
            'agent_id' => $agent->id,
            'template_id' => $template->id,
            'metadata' => ['template_id' => $template->id],
        ]);

        return $interaction;
    }

    public function processInboundWebhook(array $payload): void
    {
        ProcessInboundEmail::dispatch($payload);
    }

    private function renderTemplate(string $content, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
}
