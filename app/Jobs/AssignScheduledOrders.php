<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Picks up scheduled orders that are due for driver assignment and notifies
 * nearby candidate drivers. Runs every minute via the scheduler.
 *
 * NOTE: The real-time dispatch/matching engine lands in Phase 8. For now this
 * job pushes a ride request notification to eligible drivers in the order's
 * zone so the order surfaces to them ahead of the scheduled time.
 */
class AssignScheduledOrders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $window = (int) SystemSetting::get('driver_assign_before_minutes', 15);
        $threshold = now()->addMinutes($window);

        $orders = Order::query()
            ->whereNull('driver_id')
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $threshold)
            ->limit(100)
            ->get();

        foreach ($orders as $order) {
            $candidates = Driver::query()
                ->approved()
                ->online()
                ->limit(20)
                ->get(['id']);

            foreach ($candidates as $driver) {
                Notification::create([
                    'notifiable_type' => 'driver',
                    'notifiable_id' => $driver->id,
                    'title' => 'Scheduled trip available',
                    'body' => 'একটি scheduled order (' . $order->order_number . ') শীঘ্রই শুরু হবে। Accept করতে চান?',
                    'type' => 'order_request',
                    'data' => ['order_id' => $order->id],
                ]);
            }

            // Move it into the active matching pool.
            $order->update(['status' => 'pending']);
        }
    }
}
