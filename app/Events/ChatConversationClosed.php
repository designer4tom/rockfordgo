<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The ride ended (completed or cancelled) — both apps should disable the
 * composer and show "This chat has ended".
 */
class ChatConversationClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(
        public int $conversationId,
        public int $orderId,
        public string $reason, // completed | cancelled
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'ConversationClosed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'order_id' => $this->orderId,
            'status' => 'closed',
            'reason' => $this->reason,
            'closed_at' => now()->toISOString(),
        ];
    }
}
