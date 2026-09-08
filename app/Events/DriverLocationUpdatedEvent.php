<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DriverLocationUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(
        public int $orderId,
        public float $lat,
        public float $lng,
        public int $bearing = 0,
        public ?int $estimatedArrival = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->orderId);
    }

    public function broadcastAs(): string
    {
        return 'DriverLocationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'bearing' => $this->bearing,
            'estimated_arrival' => $this->estimatedArrival,
        ];
    }
}
