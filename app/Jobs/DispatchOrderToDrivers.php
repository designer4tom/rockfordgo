<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\DispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Thin entry point for dispatching a booked order. The actual offer/timeout
 * loop is event-driven (DispatchService + delayed CheckOrderResponseJob) — this
 * job no longer blocks a worker (no sleep()/while).
 */
class DispatchOrderToDrivers implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
        $this->onQueue('dispatch');
    }

    public function handle(DispatchService $dispatch): void
    {
        $order = Order::find($this->orderId);
        if (! $order || ! in_array($order->status, ['pending', 'scheduled'], true)) {
            return;
        }

        $dispatch->start($order);
    }
}
