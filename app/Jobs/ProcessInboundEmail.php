<?php

namespace App\Jobs;

use App\Models\Interaction;
use App\Models\InteractionAttachment;
use App\Models\EmailTemplate;
use App\Models\UnmatchedItem;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProcessInboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $rawEmail;

    public function __construct(array $rawEmail)
    {
        $this->rawEmail = $rawEmail;
    }

    public function handle(): void
    {
        $from = $this->rawEmail['from'] ?? null;
        $to = $this->rawEmail['to'] ?? null;
        $subject = $this->rawEmail['subject'] ?? '(No subject)';
        $body = $this->rawEmail['body'] ?? '';
        $messageId = $this->rawEmail['message_id'] ?? null;
        $references = $this->rawEmail['references'] ?? null;
        $attachments = $this->rawEmail['attachments'] ?? [];

        // Find contact by email
        $contact = Contact::where('email', $from)->first();

        // Find agent by recipient email (CRM agent address)
        $agent = User::where('email', $to)->first();

        $interactionData = [
            'type' => 'email',
            'direction' => 'inbound',
            'subject' => $subject,
            'body' => $body,
            'agent_id' => $agent?->id,
            'external_message_id' => $messageId,
        ];

        if ($contact) {
            $interactionData['contact_id'] = $contact->id;
            if ($contact->account_id) {
                $interactionData['account_id'] = $contact->account_id;
            }
            $interaction = Interaction::create($interactionData);

            // Handle attachments
            foreach ($attachments as $fileData) {
                $this->storeAttachment($interaction, $fileData);
            }

            // Link to existing interaction if this is a reply
            if ($references) {
                $parent = Interaction::where('external_message_id', $references)->first();
                if ($parent) {
                    $interaction->update(['parent_interaction_id' => $parent->id]);
                }
            }

            // Notify assigned agent
            if ($agent) {
                $agent->notify(new \App\Notifications\NewInteractionNotification($interaction));
            }
        } else {
            // Create unmatched item for manual review
            UnmatchedItem::create([
                'source_type' => 'email',
                'external_id' => $messageId,
                'raw_payload' => $this->rawEmail,
            ]);
        }
    }

    private function storeAttachment(Interaction $interaction, array $fileData): void
    {
        $allowedMimes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'image/png', 'image/jpeg'];

        if (!in_array($fileData['mime_type'] ?? '', $allowedMimes)) {
            return;
        }

        $maxSize = 25 * 1024 * 1024; // 25MB
        if (($fileData['size'] ?? 0) > $maxSize) {
            return;
        }

        $path = Storage::disk('s3')->put('interaction-attachments', $fileData['content']);

        InteractionAttachment::create([
            'interaction_id' => $interaction->id,
            'filename' => $fileData['filename'] ?? 'attachment',
            'mime_type' => $fileData['mime_type'],
            'size_bytes' => $fileData['size'] ?? 0,
            'storage_path' => $path,
        ]);
    }
}
