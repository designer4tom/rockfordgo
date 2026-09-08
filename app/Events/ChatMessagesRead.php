<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The other side opened the chat — lets the sender flip their ticks to "read".
 */
class ChatMessagesRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    /**
     * @param  string  $readerType  who did the reading: user|driver
     */
    public function __construct(
        public int $conversationId,
        public string $readerType,
        public int $count,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'MessagesRead';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_type' => $this->readerType,
            'read_count' => $this->count,
            'read_at' => now()->toISOString(),
        ];
    }
}
