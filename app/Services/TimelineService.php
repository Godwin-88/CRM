<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

class TimelineService
{
    /**
     * Get paginated timeline entries for a contact.
     *
     * @param  array  $filters  ['types' => string[]]  // e.g. ['interaction', 'deal', 'activity', 'ticket', 'contract']
     */
    public function getTimeline(Contact $contact, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $typeFilter = $filters['types'] ?? [];

        $allEntries = collect();

        // Interactions
        if (empty($typeFilter) || in_array('interaction', $typeFilter)) {
            $contact->interactions()->get()->each(function ($item) use (&$allEntries) {
                $allEntries->push([
                    'id' => $item->id,
                    'type' => 'interaction',
                    'type_label' => ucfirst($item->type),
                    'summary' => $item->subject,
                    'detail' => $item->body,
                    'date' => $item->created_at,
                    'agent' => $item->agent?->name ?? 'System',
                    'outcome' => $item->outcome,
                    'model_type' => 'interaction',
                ]);
            });
        }

        // Activities
        if (empty($typeFilter) || in_array('activity', $typeFilter)) {
            $contact->activities()->get()->each(function ($item) use (&$allEntries) {
                $allEntries->push([
                    'id' => $item->id,
                    'type' => 'activity',
                    'type_label' => 'Activity',
                    'summary' => $item->subject,
                    'detail' => $item->type,
                    'date' => $item->created_at,
                    'agent' => $item->assignee?->name ?? 'System',
                    'outcome' => $item->completed_at ? 'Completed' : 'Pending',
                    'model_type' => 'activity',
                ]);
            });
        }

        // Deals
        if (empty($typeFilter) || in_array('deal', $typeFilter)) {
            $contact->deals()->get()->each(function ($item) use (&$allEntries) {
                $allEntries->push([
                    'id' => $item->id,
                    'type' => 'deal',
                    'type_label' => 'Deal',
                    'summary' => $item->title.' ('.$item->stage.')',
                    'detail' => 'Value: '.($item->currency ?? 'USD').' '.number_format($item->value ?? 0, 2),
                    'date' => $item->created_at,
                    'agent' => $item->owner?->name ?? 'System',
                    'outcome' => $item->stage,
                    'model_type' => 'deal',
                ]);
            });
        }

        // Tickets
        if (empty($typeFilter) || in_array('ticket', $typeFilter)) {
            $contact->tickets()->get()->each(function ($item) use (&$allEntries) {
                $allEntries->push([
                    'id' => $item->id,
                    'type' => 'ticket',
                    'type_label' => 'Support Ticket',
                    'summary' => $item->subject,
                    'detail' => 'Priority: '.$item->priority,
                    'date' => $item->created_at,
                    'agent' => $item->assignee?->name ?? 'System',
                    'outcome' => $item->status,
                    'model_type' => 'ticket',
                ]);
            });
        }

        // Contracts
        if (empty($typeFilter) || in_array('contract', $typeFilter)) {
            $contact->contracts()->get()->each(function ($item) use (&$allEntries) {
                $allEntries->push([
                    'id' => $item->id,
                    'type' => 'contract',
                    'type_label' => 'Contract',
                    'summary' => $item->title,
                    'detail' => 'Status: '.$item->status,
                    'date' => $item->created_at,
                    'agent' => 'System',
                    'outcome' => $item->status,
                    'model_type' => 'contract',
                ]);
            });
        }

        // Sort by date descending
        $sorted = $allEntries->sortByDesc('date')->values();

        // Manual pagination
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
