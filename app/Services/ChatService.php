<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\ChatSession;
use App\Models\UnmatchedItem;
use App\Models\Contact;
use Illuminate\Support\Facades\Cache;

class ChatService
{
    private int $defaultTimeout = 180; // 3 minutes
    private int $maxConcurrentPerAgent = 3;

    public function startSession(array $data): ChatSession
    {
        $visitorToken = $data['visitor_token'] ?? uniqid();
        $contact = null;

        if (!empty($data['email'])) {
            $contact = Contact::where('email', $data['email'])->first();
        }

        // Create interaction
        $interaction = Interaction::create([
            'contact_id' => $contact?->id,
            'type' => 'chat',
            'direction' => 'inbound',
            'subject' => $data['subject'] ?? 'Live chat session',
            'body' => $data['initial_message'] ?? '',
            'agent_id' => null,
            'metadata' => [
                'visitor_token' => $visitorToken,
                'visitor_email' => $data['email'] ?? null,
                'visitor_name' => $data['name'] ?? null,
            ],
        ]);

        $session = ChatSession::create([
            'interaction_id' => $interaction->id,
            'visitor_token' => $visitorToken,
            'visitor_email' => $data['email'] ?? null,
            'visitor_name' => $data['name'] ?? null,
            'matched_contact_id' => $contact?->id,
            'status' => 'waiting',
            'metadata' => $data,
        ]);

        // Broadcast waiting session via WebSocket
        $this->broadcastNewChat($session);

        return $session;
    }

    public function acceptSession(string $sessionId, string $agentId): ChatSession
    {
        $session = ChatSession::with('interaction')->findOrFail($sessionId);

        if ($session->status !== 'waiting') {
            throw new \Exception('Session is no longer waiting.');
        }

        $activeCount = ChatSession::where('assigned_agent_id', $agentId)
            ->where('status', 'active')
            ->count();

        if ($activeCount >= $this->maxConcurrentPerAgent) {
            throw new \Exception('Agent has reached maximum concurrent chat limit.');
        }

        $session->update([
            'status' => 'active',
            'assigned_agent_id' => $agentId,
        ]);

        $session->interaction->update([
            'agent_id' => $agentId,
        ]);

        // Notify customer that agent has joined
        $this->broadcastAgentJoined($session);

        return $session;
    }

    public function addMessage(string $sessionId, string $sender, string $message): void
    {
        $session = ChatSession::with('interaction')->findOrFail($sessionId);
        $isCustomer = $sender === 'customer';

        // Append to interaction body
        $prefix = $isCustomer ? 'Customer' : 'Agent';
        $session->interaction->body .= "\n[{$prefix}] " . $message;

        if (strlen($session->interaction->body) > 65535) {
            $session->interaction->body = substr($session->interaction->body, -65535);
        }
        $session->interaction->save();

        // Broadcast message
        $this->broadcastMessage($session, $sender, $message);

        // Check for timeout
        if ($session->status === 'waiting') {
            $waitSeconds = $session->wait_time_seconds ?? 0;
            if ($waitSeconds >= $this->defaultTimeout) {
                $this->handleTimeout($session);
            } else {
                $session->increment('wait_time_seconds');
            }
        }
    }

    public function closeSession(string $sessionId, ?string $outcome = null, ?string $dealId = null, ?string $ticketId = null): ChatSession
    {
        $session = ChatSession::with('interaction')->findOrFail($sessionId);

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        if ($outcome) {
            $session->interaction->update(['outcome' => $outcome]);
        }
        if ($dealId) {
            $session->interaction->update(['deal_id' => $dealId]);
        }
        if ($ticketId) {
            $session->interaction->update(['deal_id' => $ticketId]);
        }

        $this->broadcastSessionClosed($session);

        return $session;
    }

    private function handleTimeout(ChatSession $session): void
    {
        $session->update([
            'status' => 'no_answer',
            'closed_at' => now(),
        ]);

        // Create callback task
        \App\Models\Activity::create([
            'subject' => 'Callback for missed chat: ' . ($session->visitor_name ?? 'Unknown'),
            'type' => 'task',
            'contact_id' => $session->matched_contact_id,
            'assigned_to' => $session->assigned_agent_id,
            'priority' => 'high',
            'due_at' => now()->addHours(1),
        ]);

        $this->broadcastTimeout($session);
    }

    private function broadcastNewChat(ChatSession $session): void
    {
        broadcast(new \App\Events\NewChatSession($session))->toOthers();
    }

    private function broadcastAgentJoined(ChatSession $session): void
    {
        broadcast(new \App\Events\AgentJoinedChat($session))->toOthers();
    }

    private function broadcastMessage(ChatSession $session, string $sender, string $message): void
    {
        broadcast(new \App\Events\ChatMessageReceived($session, $sender, $message))->toOthers();
    }

    private function broadcastSessionClosed(ChatSession $session): void
    {
        broadcast(new \App\Events\ChatSessionClosed($session))->toOthers();
    }

    private function broadcastTimeout(ChatSession $session): void
    {
        broadcast(new \App\Events\ChatSessionTimeout($session))->toOthers();
    }
}
