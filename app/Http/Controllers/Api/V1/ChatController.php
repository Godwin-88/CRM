<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Interaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class ChatController extends Controller
{
    public function __construct(protected \App\Services\ChatService $chatService) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'initial_message' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
        ]);

        $session = $this->chatService->startSession([
            'subject' => $validated['subject'] ?? 'Live Chat',
            'initial_message' => $validated['initial_message'] ?? '',
            'email' => $validated['email'] ?? null,
            'name' => $validated['name'] ?? null,
            'visitor_token' => $request->header('X-Visitor-Token') ?? uniqid(),
        ]);

        return response()->json($session->load('interaction'), 201);
    }

    public function accept(Request $request, string $sessionId): JsonResponse
    {
        $agentId = auth()->id();
        $session = $this->chatService->acceptSession($sessionId, $agentId);

        return response()->json($session->load('interaction'));
    }

    public function message(Request $request, string $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'sender' => 'required|in:customer,agent',
            'message' => 'required|string|max:5000',
        ]);

        $this->chatService->addMessage($sessionId, $validated['sender'], $validated['message']);

        return response()->json(['success' => true]);
    }

    public function close(Request $request, string $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'outcome' => 'nullable|string|max:255',
            'deal_id' => 'nullable|exists:deals,id',
            'ticket_id' => 'nullable|exists:tickets,id',
        ]);

        $session = $this->chatService->closeSession(
            $sessionId,
            $validated['outcome'] ?? null,
            $validated['deal_id'] ?? null,
            $validated['ticket_id'] ?? null
        );

        return response()->json($session->load('interaction'));
    }

    public function waiting(): JsonResponse
    {
        $sessions = ChatSession::with(['interaction.contact', 'assignedAgent'])
            ->where('status', 'waiting')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($sessions);
    }
}
