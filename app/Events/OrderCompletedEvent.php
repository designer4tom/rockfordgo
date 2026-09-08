<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $queue = 'broadcasts';

    public function __construct(public Order $order)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->order->id);
    }

    public function broadcastAs(): string
    {
        return 'OrderCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'fare_breakdown' => [
                'base_fare' => number_format((float) $this->order->base_fare, 2, '.', ''),
                'distance_charge' => number_format((float) $this->order->distance_charge, 2, '.', ''),
                'time_charge' => number_format((float) $this->order->time_charge, 2, '.', ''),
                'delivery_charge' => number_format((float) $this->order->delivery_charge, 2, '.', ''),
                'surge_amount' => number_format((float) $this->order->surge_amount, 2, '.', ''),
                'coupon_discount' => number_format((float) $this->order->coupon_discount, 2, '.', ''),
                'tip' => number_format((float) $this->order->tip_amount, 2, '.', ''),
                'total' => number_format((float) $this->order->total_amount, 2, '.', ''),
            ],
            'payment_status' => $this->order->payment_status,
        ];
    }
}
