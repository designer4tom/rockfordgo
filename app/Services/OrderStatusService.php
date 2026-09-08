<?php

namespace App\Services;

use App\Events\OrderStatusUpdatedEvent;
use App\Models\Order;

/**
 * Single source of truth for order status changes: updates the status + its
 * timestamp, broadcasts the real-time event, and notifies the right party.
 * Every status transition should go through here (no scattered updates).
 */
class OrderStatusService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    private const TIMESTAMP = [
        'accepted' => 'accepted_at',
        'confirm_arrival' => 'arrived_at',
        'picked_up' => 'picked_up_at',
        'start_ride' => 'started_at',
        'dropped_off' => 'dropped_at',
        'completed' => 'completed_at',
        'cancelled' => 'cancelled_at',
    ];

    public function updateStatus(Order $order, string $newStatus, array $extra = []): Order
    {
        $update = ['status' => $newStatus] + $extra;
        if (isset(self::TIMESTAMP[$newStatus]) && empty($extra[self::TIMESTAMP[$newStatus]])) {
            $update[self::TIMESTAMP[$newStatus]] = now();
        }
        $order->update($update);
        $order->refresh();

        // Real-time event on the order channel (app subscribes order.{id}).
        broadcast(new OrderStatusUpdatedEvent($order->id, $newStatus, $this->nextAction($newStatus)));

        $this->notify($order, $newStatus);

        return $order;
    }

    // Driver-app hint for the next action of each status.
    public function nextAction(string $status): string
    {
        return [
            'go_to_pickup' => "Press 'Arrived' when you reach the pickup point",
            'confirm_arrival' => 'Ask the customer for the OTP to start the trip',
            'picked_up' => "Press 'Start' to begin the ride",
            'start_ride' => "Press 'Reached' at the destination",
            'dropped_off' => "Press 'Complete' to finish the trip",
            'completed' => 'Trip completed',
        ][$status] ?? '';
    }

    // Notify the relevant party per status (FCM + in-app, deduped by order id).
    private function notify(Order $order, string $status): void
    {
        $data = ['order_id' => (string) $order->id, 'order_number' => (string) $order->order_number, 'status' => $status];

        [$title, $body] = match ($status) {
            'accepted' => ['Driver assigned', 'A driver has accepted your request and is on the way.'],
            'go_to_pickup' => ['Driver on the way', 'Your driver is heading to the pickup point.'],
            'confirm_arrival' => ['Driver arrived', 'Your driver has arrived at the pickup point.'],
            'picked_up' => [$order->type === 'parcel' ? 'Parcel picked up' : 'Trip started', $order->type === 'parcel' ? 'Your parcel has been picked up.' : 'Your trip has started.'],
            'start_ride' => ['Trip started', 'Enjoy your ride.'],
            'completed' => ['Completed', $order->type === 'parcel' ? 'Delivery completed.' : 'Trip completed. Thanks for riding with ReadyRide!'],
            'no_driver_found' => ['No driver found', 'Sorry, no driver is available right now. Please try again.'],
            'cancelled' => ['Order cancelled', 'The order has been cancelled.'],
            default => [null, null],
        };

        if (! $title) {
            return;
        }

        // Cancellations notify the OTHER party; everything else notifies the customer.
        if ($status === 'cancelled') {
            if (($order->cancelled_by ?? 'user') === 'user' && $order->driver_id) {
                $this->notifications->sendPush('driver', $order->driver_id, $title, 'The customer cancelled the order.', 'order_update', $data);
            } elseif ($order->user_id) {
                $this->notifications->sendPush('user', $order->user_id, $title, 'Your order was cancelled by the driver.', 'order_update', $data);
            }

            return;
        }

        if ($order->user_id) {
            // Map the two statuses that are Notification Rule events; route those
            // through the rule engine (push + SMS), everything else stays push-only.
            $ruleEvent = match ($status) {
                'confirm_arrival' => 'driver_arrived',
                'completed' => 'delivery_complete',
                default => null,
            };

            if ($ruleEvent) {
                $phone = \App\Models\User::whereKey($order->user_id)->value('phone');
                $this->notifications->notifyEvent($ruleEvent, 'user', $order->user_id, $title, $body, 'order_update', $data, $phone);
            } else {
                $this->notifications->sendPush('user', $order->user_id, $title, $body, 'order_update', $data);
            }
        }

        // Parcel COD: tell the sender their product price hit the wallet on completion.
        if ($status === 'completed' && $order->type === 'parcel' && $order->is_cod && $order->user_id) {
            $this->notifications->sendPush('user', $order->user_id, 'Wallet credited',
                'The product price has been added to your wallet.', 'order_update', $data);
        }
    }
}
