<?php

namespace App\Services;

use App\Events\NewInteractionNotification;
use App\Models\Interaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InteractionService
{
    public function getInbox(string $agentId, bool $teamView = false, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Interaction::query()
            ->with(['contact', 'agent', 'channel', 'deal', 'ticket', 'attachments']);

        if (! $teamView && $agentId !== 'all') {
            $query->where('agent_id', $agentId);
        }

        // Filters
        if (! empty($filters['channel'])) {
            $query->where('type', $filters['channel']);
        }
        if (! empty($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }
        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (! empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['is_reviewed'])) {
            $query->where('is_reviewed', $filters['is_reviewed']);
        }
        if (! empty($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function markAsReviewed(string $interactionId, string $agentId): Interaction
    {
        $interaction = Interaction::where('id', $interactionId)
            ->where(function ($q) use ($agentId) {
                $q->where('agent_id', $agentId)
                    ->orWhere('is_locked', false);
            })
            ->firstOrFail();

        $interaction->update(['is_reviewed' => true]);

        return $interaction;
    }

    public function createInteraction(array $data): Interaction
    {
        $interaction = Interaction::create($data);

        if ($interaction->agent_id) {
            broadcast(new \App\Events\NewInteractionNotification($interaction))->toOthers();
        }

        return $interaction;
    }

    public function getDetail(string $interactionId): Interaction
    {
        return Interaction::with([
            'contact',
            'agent',
            'channel',
            'attachments',
            'callRecording',
            'chatSession',
            'contact.interactions' => function ($q) {
                $q->latest()->limit(3);
            },
        ])->findOrFail($interactionId);
    }

    public function lockInteraction(string $interactionId, string $agentId): Interaction
    {
        $interaction = Interaction::findOrFail($interactionId);

        if ($interaction->is_locked && $interaction->locked_by !== $agentId) {
            throw new \Exception('Interaction is already locked by another user.');
        }

        $interaction->update([
            'is_locked' => true,
            'locked_by' => $agentId,
            'locked_at' => now(),
        ]);

        return $interaction;
    }

    public function unlockInteraction(string $interactionId, string $agentId): Interaction
    {
        $interaction = Interaction::findOrFail($interactionId);

        if ((string) $interaction->locked_by !== (string) $agentId) {
            throw new \Exception('You cannot unlock an interaction locked by another user.');
        }

        $interaction->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $interaction;
    }
}
