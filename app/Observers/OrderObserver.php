<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Support\Facades\Log;

/**
 * Drives the chat lifecycle from the order's own status.
 *
 * Deliberately an observer rather than calls inside OrderStatusService: five
 * different places change an order's status (the service, the customer ride and
 * parcel cancels, scheduled-order cancel, and the admin intervention screen).
 * Hooking the model catches all of them — and anything added later — so a chat
 * can never be left open on a finished ride.
 */
class OrderObserver
{
    // Statuses that end an order and therefore end its chat.
    private const CLOSING = ['completed', 'cancelled', 'rejected', 'no_driver_found'];

    public function updated(Order $order): void
    {
        // Only react when the status actually changed.
        if (! $order->wasChanged('status')) {
            // A driver assigned to an already-accepted order (re-dispatch).
            if ($order->wasChanged('driver_id') && $order->driver_id && $this->isOngoing($order)) {
                $this->safely(fn () => app(ChatService::class)->openForOrder($order));
            }

            return;
        }

        $status = $order->status;

        if ($status === 'accepted' && $order->driver_id) {
            $this->safely(fn () => app(ChatService::class)->openForOrder($order));

            return;
        }

        if (in_array($status, self::CLOSING, true)) {
            $reason = $status === 'completed' ? 'completed' : 'cancelled';
            $this->safely(fn () => app(ChatService::class)->closeForOrder($order, $reason));
        }
    }

    private function isOngoing(Order $order): bool
    {
        return in_array($order->status, Order::ONGOING_STATUSES, true);
    }

    // Chat must never break the ride flow — log and carry on.
    private function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::error('Chat lifecycle error: ' . $e->getMessage());
        }
    }
}
