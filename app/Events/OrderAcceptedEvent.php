<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Storage;

class OrderAcceptedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(public Order $order, public Driver $driver)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->order->id);
    }

    public function broadcastAs(): string
    {
        return 'DriverAccepted';
    }

    public function broadcastWith(): array
    {
        $v = $this->driver->activeVehicle;

        return [
            'driver' => [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'phone' => $this->driver->phone,
                'avatar' => $this->driver->avatar ? Storage::url($this->driver->avatar) : null,
                'rating' => (float) $this->driver->average_rating,
                'vehicle' => [
                    'make' => $v?->make,
                    'model' => $v?->model,
                    'color' => $v?->color,
                    'registration_number' => $v?->registration_number,
                ],
                'current_lat' => (float) $this->driver->current_lat,
                'current_lng' => (float) $this->driver->current_lng,
            ],
            'estimated_arrival' => 5,
        ];
    }
}
