<?php

namespace App\Services;

use App\Events\TicketEscalated;
use App\Events\TicketMerged;
use App\Events\TicketSplit;
use App\Events\TicketStatusChanged;
use App\Models\Activity;
use App\Models\BusinessHours;
use App\Models\Interaction;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketInternalNote;
use App\Models\TicketRating;
use App\Models\User;
use App\Notifications\InternalNoteMentioned;
use App\Notifications\ManagerEscalation;
use App\Notifications\TicketAssigned;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TicketService
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING_ON_CUSTOMER = 'waiting_on_customer';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected array $statusTransitions = [
        self::STATUS_OPEN => [self::STATUS_IN_PROGRESS, self::STATUS_WAITING_ON_CUSTOMER],
        self::STATUS_IN_PROGRESS => [self::STATUS_WAITING_ON_CUSTOMER, self::STATUS_RESOLVED, self::STATUS_OPEN],
        self::STATUS_WAITING_ON_CUSTOMER => [self::STATUS_IN_PROGRESS, self::STATUS_OPEN],
        self::STATUS_RESOLVED => [self::STATUS_CLOSED], // Re-opening handled separately with time check
        self::STATUS_CLOSED => [],
    ];

    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::create([
                'subject' => $data['subject'],
                'description' => $data['description'] ?? null,
                'contact_id' => $data['contact_id'],
                'account_id' => $data['account_id'] ?? null,
                'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
                'category_id' => $data['category_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'is_agent_created' => $data['is_agent_created'] ?? false,
            ]);

            if (isset($data['form_response']) && isset($data['category_id'])) {
                $ticket->formResponse()->create([
                    'ticket_form_id' => TicketCategory::find($data['category_id'])?->form?->id,
                    'ticket_category_id' => $data['category_id'],
                    'response_data' => $data['form_response'],
                ]);
            }

            return $ticket;
        });
    }

    public function assignTicket(Ticket $ticket, User $agent, ?User $assigner = null): void
    {
        if ($ticket->assigned_to === $agent->id) {
            return;
        }

        $oldAssignee = $ticket->assignee;
        $ticket->update(['assigned_to' => $agent->id]);

        Notification::send($agent, new TicketAssigned($ticket, $assigner));
    }

    public function escalateTicket(Ticket $ticket, User $escalatedBy, string $reason): void
    {
        if (! $ticket->escalation_reason) {
            throw new \InvalidArgumentException('Escalation reason is required.');
        }

        $ticket->escalation_reason = $reason;
        $ticket->save();

        $newPriority = $this->raisePriority($ticket->priority);
        $ticket->update(['priority' => $newPriority]);

        $manager = $this->getManagerForUser($escalatedBy);

        TicketEscalated::dispatch($ticket, $escalatedBy, $manager);

        if ($manager) {
            Notification::send($manager, new ManagerEscalation($ticket, $escalatedBy));
        }
    }

    public function resolveTicket(Ticket $ticket, string $resolutionNote, User $agent): void
    {
        if (empty($resolutionNote)) {
            throw new \InvalidArgumentException('Resolution note is required.');
        }

        $ticket->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        $ticket->internalNotes()->create([
            'author_id' => $agent->id,
            'body' => "Resolution: {$resolutionNote}",
        ]);

        TicketStatusChanged::dispatch($ticket, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED);
    }

    public function closeTicket(Ticket $ticket): void
    {
        if ($ticket->status !== self::STATUS_RESOLVED) {
            throw new \InvalidArgumentException('Only resolved tickets can be closed.');
        }

        $ticket->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        TicketStatusChanged::dispatch($ticket, self::STATUS_RESOLVED, self::STATUS_CLOSED);
    }

    public function reopenTicket(Ticket $ticket): void
    {
        if (! $ticket->canBeReopened()) {
            throw new \InvalidArgumentException('Ticket cannot be reopened after 7 days of resolution.');
        }

        $ticket->update([
            'status' => self::STATUS_OPEN,
            'resolved_at' => null,
        ]);

        TicketStatusChanged::dispatch($ticket, self::STATUS_RESOLVED, self::STATUS_OPEN);
    }

    public function changeStatus(Ticket $ticket, string $newStatus): void
    {
        $oldStatus = $ticket->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        if (! isset($this->statusTransitions[$oldStatus]) ||
            ! in_array($newStatus, $this->statusTransitions[$oldStatus])) {
            throw new \InvalidArgumentException("Invalid status transition from {$oldStatus} to {$newStatus}");
        }

        // Handle reopening resolved tickets
        if ($newStatus === self::STATUS_OPEN && $oldStatus === self::STATUS_RESOLVED) {
            $this->reopenTicket($ticket);

            return;
        }

        $ticket->update(['status' => $newStatus]);

        TicketStatusChanged::dispatch($ticket, $oldStatus, $newStatus);
    }

    public function mergeTickets(Ticket $sourceTicket, Ticket $targetTicket): void
    {
        if (! auth()->user()?->hasRole(['manager', 'admin'])) {
            throw new \AuthorizationException('Only managers and admins can merge tickets.');
        }

        DB::transaction(function () use ($sourceTicket, $targetTicket) {
            // Move interactions
            $sourceTicket->interactions()->update(['ticket_id' => $targetTicket->id]);

            // Move internal notes
            $sourceTicket->internalNotes()->update(['ticket_id' => $targetTicket->id]);

            // Move media/attachments
            $sourceTicket->media()->update(['model_id' => $targetTicket->id]);

            // Soft delete source ticket
            $sourceTicket->update([
                'merged_into_ticket_id' => $targetTicket->id,
                'merged_at' => now(),
            ]);

            // Log deletion (soft-deleted tickets still exist)
            $sourceTicket->delete();
        });

        TicketMerged::dispatch($sourceTicket, $targetTicket);
    }

    public function splitTicket(Ticket $originalTicket, array $interactionIds, User $agent): Ticket
    {
        if (! auth()->user()?->hasRole(['manager', 'admin'])) {
            throw new \AuthorizationException('Only managers and admins can split tickets.');
        }

        return DB::transaction(function () use ($originalTicket, $interactionIds) {
            $newTicket = Ticket::create([
                'subject' => $originalTicket->subject,
                'description' => $originalTicket->description,
                'contact_id' => $originalTicket->contact_id,
                'account_id' => $originalTicket->account_id,
                'priority' => $originalTicket->priority,
                'status' => self::STATUS_OPEN,
                'category_id' => $originalTicket->category_id,
                'split_from_ticket_id' => $originalTicket->id,
            ]);

            // Move selected interactions
            Interaction::whereIn('id', $interactionIds)
                ->where('ticket_id', $originalTicket->id)
                ->update(['ticket_id' => $newTicket->id]);

            TicketSplit::dispatch($originalTicket, $newTicket);

            return $newTicket;
        });
    }

    public function linkTickets(Ticket $ticket, Ticket $relatedTicket): void
    {
        $ticket->relatedTickets()->attach($relatedTicket->id);
        $relatedTicket->relatedTickets()->attach($ticket->id);
    }

    public function unlinkTickets(Ticket $ticket, Ticket $relatedTicket): void
    {
        $ticket->relatedTickets()->detach($relatedTicket->id);
        $relatedTicket->relatedTickets()->detach($ticket->id);
    }

    public function linkArticle(Ticket $ticket, KnowledgeBaseArticle $article): void
    {
        $ticket->linkedArticles()->attach($article->id);
    }

    public function unlinkArticle(Ticket $ticket, KnowledgeBaseArticle $article): void
    {
        $ticket->linkedArticles()->detach($article->id);
    }

    public function addInternalNote(Ticket $ticket, string $body, User $author, array $mentions = []): TicketInternalNote
    {
        $note = $ticket->internalNotes()->create([
            'author_id' => $author->id,
            'body' => $body,
        ]);

        foreach ($mentions as $userId) {
            $note->mentions()->create(['user_id' => $userId]);
            $mentionedUser = User::find($userId);
            if ($mentionedUser) {
                Notification::send($mentionedUser, new InternalNoteMentioned($ticket, $note, $mentionedUser));
            }
        }

        return $note;
    }

    public function editInternalNote(TicketInternalNote $note, string $newBody, User $editor): void
    {
        if ($note->author_id !== $editor->id && ! $editor->hasRole(['manager', 'admin'])) {
            throw new \AuthorizationException('Only the author or managers can edit notes.');
        }

        if ($note->isLocked()) {
            throw new \InvalidArgumentException('Note can only be edited within 30 minutes of posting.');
        }

        $note->update([
            'body' => $newBody,
            'edited_at' => now(),
        ]);
    }

    public function deleteInternalNote(TicketInternalNote $note, User $deleter): void
    {
        if ($note->author_id !== $deleter->id && ! $deleter->hasRole(['manager', 'admin'])) {
            throw new \AuthorizationException('Only the author or managers can delete notes.');
        }

        $note->update([
            'deleted_at' => now(),
            'deleted_by_id' => $deleter->id,
        ]);
    }

    public function recordRating(Ticket $ticket, int $score, ?string $comment = null): TicketRating
    {
        if ($ticket->rating) {
            throw new \InvalidArgumentException('Ticket already has a rating.');
        }

        if ($ticket->is_agent_created) {
            throw new \InvalidArgumentException('Agent-created tickets are excluded from CSAT.');
        }

        return $ticket->rating()->create([
            'score' => $score,
            'comment' => $comment,
            'submitted_at' => now(),
        ]);
    }

    public function maybeCreateFollowUpActivity(TicketRating $rating): ?Activity
    {
        if ($rating->score > 2) {
            return null;
        }

        $ticket = $rating->ticket;
        $agent = $ticket->assignee;

        if (! $agent) {
            return null;
        }

        return Activity::create([
            'subject' => 'Follow-up on low CSAT rating',
            'type' => 'task',
            'due_at' => $this->nextBusinessDay(),
            'contact_id' => $ticket->contact_id,
            'assigned_to' => $agent->id,
        ]);
    }

    public function getManagerForUser(User $user): ?User
    {
        $team = Team::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('role', 'manager');
        })->first();

        if (! $team) {
            return null;
        }

        $managerAssignment = TeamMember::where('team_id', $team->id)
            ->where('role', 'manager')
            ->first();

        return $managerAssignment?->user;
    }

    protected function raisePriority(string $currentPriority): string
    {
        $priorityOrder = [
            self::PRIORITY_LOW => self::PRIORITY_MEDIUM,
            self::PRIORITY_MEDIUM => self::PRIORITY_HIGH,
            self::PRIORITY_HIGH => self::PRIORITY_URGENT,
            self::PRIORITY_URGENT => self::PRIORITY_URGENT,
        ];

        return $priorityOrder[$currentPriority] ?? self::PRIORITY_URGENT;
    }

    protected function nextBusinessDay(): Carbon
    {
        $date = Carbon::tomorrow();
        $businessHours = BusinessHours::where('is_global', true)->first();

        if (! $businessHours) {
            return $date->setHour(9)->setMinute(0);
        }

        $daysOfWeek = $businessHours->days_of_week ?? [1, 2, 3, 4, 5];
        while (! in_array($date->dayOfWeek, $daysOfWeek)) {
            $date->addDay();
        }

        $startTime = Carbon::createFromFormat('H:i', $businessHours->start_time);

        return $date->setTime($startTime->hour, $startTime->minute);
    }
}
