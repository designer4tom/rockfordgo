<?php

namespace App\Events;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NewOrderRequestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(public Driver $driver, public Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('driver.' . $this->driver->id);
    }

    public function broadcastAs(): string
    {
        return 'NewOrderRequest';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'type' => $this->order->type,
            'pickup' => [
                'address' => $this->order->pickup_address,
                'lat' => (float) $this->order->pickup_lat,
                'lng' => (float) $this->order->pickup_lng,
            ],
            'drop' => [
                'address' => $this->order->drop_address,
                'lat' => (float) $this->order->drop_lat,
                'lng' => (float) $this->order->drop_lng,
            ],
            'distance_km' => (float) $this->order->distance_km,
            'estimated_earning' => number_format((float) $this->order->driver_earning, 2, '.', ''),
            'payment_method' => $this->order->payment_method,
            'cod_amount' => $this->order->is_cod ? number_format((float) $this->order->cod_amount, 2, '.', '') : null,
            'timeout_seconds' => 30,
        ];
    }
}
