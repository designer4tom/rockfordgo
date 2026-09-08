<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderRequestCancelledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(public int $driverId, public int $orderId)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('driver.' . $this->driverId);
    }

    public function broadcastAs(): string
    {
        return 'OrderRequestCancelled';
    }

    public function broadcastWith(): array
    {
        return ['order_id' => $this->orderId];
    }
}
