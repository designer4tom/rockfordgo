<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCancelledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(
        public int $orderId,
        public string $cancelledBy,
        public ?string $reason = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->orderId);
    }

    public function broadcastAs(): string
    {
        return 'OrderCancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'cancelled_by' => $this->cancelledBy,
            'reason' => $this->reason,
        ];
    }
}
