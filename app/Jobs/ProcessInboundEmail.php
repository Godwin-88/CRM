<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\SupportEmailAddress;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $emailData,
        public ?SupportEmailAddress $supportEmail = null
    ) {}

    public function handle(TicketService $ticketService): void
    {
        $fromEmail = $this->emailData['from'] ?? null;
        $subject = $this->emailData['subject'] ?? '';
        $body = $this->emailData['body'] ?? '';
        $headers = $this->emailData['headers'] ?? [];

        // Check for auto-reply headers
        if ($this->isAutoReply($headers)) {
            Log::info('Discarded auto-reply email', ['from' => $fromEmail]);

            return;
        }

        // Check for spam markers
        if ($this->isSpam($body, $headers)) {
            Log::info('Discarded spam email', ['from' => $fromEmail]);

            return;
        }

        // Try to find ticket reference in subject
        if (preg_match('/\[Ticket\s*#([^\]]+)\]/i', $subject, $matches)) {
            $ticketRef = $matches[1];
            $ticket = Ticket::where('id', $ticketRef)
                ->orWhere('subject', 'like', "%[Ticket #{$ticketRef}%")
                ->first();

            if ($ticket) {
                $this->createTicketInteraction($ticket, $fromEmail, $subject, $body);

                return;
            }
        }

        // Create new ticket
        $contact = null;
        if ($fromEmail) {
            $contact = Contact::where('email', $fromEmail)->first();
        }

        if (! $contact && $fromEmail) {
            $contact = $this->createContactFromEmail($fromEmail, $this->emailData['from_name'] ?? null);
        }

        $ticket = $ticketService->createTicket([
            'subject' => $subject,
            'description' => $body,
            'contact_id' => $contact?->id,
            'priority' => $this->supportEmail?->default_priority ?? 'medium',
            'category_id' => $this->supportEmail?->default_category_id,
        ]);

        $this->createTicketInteraction($ticket, $fromEmail, $subject, $body);
    }

    protected function isAutoReply(array $headers): bool
    {
        $autoReplyHeaders = ['Auto-Submitted', 'X-Autoreply', 'X-Auto-Reply', 'Precedence'];

        foreach ($autoReplyHeaders as $header) {
            if (! empty($headers[$header])) {
                return true;
            }
        }

        return false;
    }

    protected function isSpam(string $body, array $headers): bool
    {
        // Basic spam check - can be extended with more sophisticated logic
        $spamIndicators = ['spam', 'viagra', 'casino', 'lottery'];

        $content = strtolower($body.' '.($headers['Subject'] ?? ''));
        foreach ($spamIndicators as $indicator) {
            if (str_contains($content, $indicator)) {
                return true;
            }
        }

        return false;
    }

    protected function createContactFromEmail(string $email, ?string $name): Contact
    {
        $nameParts = $name ? explode(' ', $name, 2) : [null, null];

        return Contact::create([
            'email' => $email,
            'first_name' => $nameParts[0] ?? 'Unknown',
            'last_name' => $nameParts[1] ?? '',
            'type' => 'customer',
        ]);
    }

    protected function createTicketInteraction(Ticket $ticket, ?string $fromEmail, string $subject, string $body): void
    {
        Interaction::create([
            'contact_id' => $ticket->contact_id,
            'account_id' => $ticket->account_id,
            'type' => 'email',
            'direction' => 'inbound',
            'subject' => $subject,
            'body' => $body,
            'ticket_id' => $ticket->id,
        ]);
    }
}
