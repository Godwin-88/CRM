<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsatController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function store(int $score, Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->rating) {
            return response()->json([
                'message' => 'Thank you! Your rating has already been recorded.',
            ], 200);
        }

        $validated = $request->validate([
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $rating = $this->ticketService->recordRating($ticket, $validated['score'], $validated['comment'] ?? null);

        // Create follow-up activity if needed
        $this->ticketService->maybeCreateFollowUpActivity($rating);

        return response()->json([
            'message' => 'Thank you for your feedback!',
            'rating' => $rating,
        ]);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $rating = $ticket->rating;

        if (!$rating) {
            return response()->json([
                'message' => 'No rating found for this ticket.',
            ], 404);
        }

        return response()->json($rating);
    }

    public function analytics(Request $request): JsonResponse
    {
        $query = TicketRating::query()->with('ticket.assignee', 'ticket.category');

        if ($request->filled('agent_id')) {
            $query->whereHas('ticket', fn($q) => $q->where('assigned_to', $request->agent_id));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('ticket', fn($q) => $q->where('category_id', $request->category_id));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('submitted_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('submitted_at', '<=', $request->to_date);
        }

        $total = $query->count();
        $avg = $query->avg('score');

        $byAgent = TicketRating::query()
            ->join('tickets', 'ticket_ratings.ticket_id', '=', 'tickets.id')
            ->selectRaw('tickets.assigned_to, AVG(score) as avg_score, COUNT(*) as count')
            ->groupBy('tickets.assigned_to')
            ->get();

        return response()->json([
            'total_ratings' => $total,
            'average_score' => round($avg, 2),
            'by_agent' => $byAgent,
        ]);
    }
}