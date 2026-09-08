<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new chat message. Both apps listen on the conversation channel.
 */
class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(public Message $message, public string $senderName)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => (int) $this->message->conversation_id,
                'sender_type' => $this->message->sender_type,
                'sender_id' => (int) $this->message->sender_id,
                'sender_name' => $this->senderName,
                'body' => $this->message->body,
                'is_read' => false,
                'created_at' => $this->message->created_at->toISOString(),
            ],
        ];
    }
}
