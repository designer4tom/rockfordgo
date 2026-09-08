<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderStatusUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(
        public int $orderId,
        public string $status,
        public string $message = '',
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->orderId);
    }

    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }
}
