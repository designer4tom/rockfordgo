<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Services\DispatchService;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OrderInterventionController extends Controller implements HasMiddleware
{
    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
        private DispatchService $dispatch,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders,write'),
            // Force-complete on an ongoing order is restricted to super admins.
            new Middleware('admin.super', only: ['forceComplete']),
        ];
    }

    // Admin cancels an order, optionally refunding the customer.
    public function cancel(Request $request, string $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'refund' => ['nullable', 'boolean'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = Order::with('user')->findOrFail($id);

        if (! $order->isCancellable()) {
            return back()->with('error', 'This order can no longer be cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_by' => 'admin',
            'cancellation_reason' => $data['reason'],
            'cancelled_at' => now(),
        ]);

        $this->dispatch->record($order, 'cancelled', $order->driver_id, ['meta' => ['by' => 'admin', 'reason' => $data['reason']]]);

        // Refund to wallet only when requested and the order was actually paid.
        $refundIssued = false;
        $wantsRefund = ! empty($data['refund']);
        $amount = (float) ($data['refund_amount'] ?? $order->total_amount);

        if ($wantsRefund && $amount > 0 && $order->payment_status === 'paid' && $order->user) {
            $this->wallet->refundToCustomer(
                $order->user,
                $amount,
                $order->id,
                'Order ' . $order->order_number . ' cancelled by admin: ' . $data['reason']
            );
            $refundIssued = true;
        }

        // Notify both parties.
        if ($order->user) {
            $this->notify('user', $order->user_id, 'Order cancelled',
                'Your order ' . $order->order_number . ' has been cancelled. Reason: ' . $data['reason'], 'order_update');
        }
        if ($order->driver_id) {
            $this->notify('driver', $order->driver_id, 'Trip cancelled',
                'Order ' . $order->order_number . ' has been cancelled by admin.', 'order_update');
        }

        $msg = 'Order ' . $order->order_number . ' cancelled.';
        if ($refundIssued) {
            $msg .= ' Refund credited to customer wallet.';
        }
        // COD reconciliation reminder.
        if ($order->is_cod) {
            return back()->with('warning', $msg . ' COD order — please reconcile any collected cash amount manually.');
        }

        return back()->with('success', $msg);
    }

    // Assign a driver to an order, or reassign an active one to a different driver.
    // Works for unassigned orders too (pending / no_driver_found) — the main reason
    // an admin steps in when auto-dispatch found nobody.
    public function reassign(Request $request, string $id)
    {
        $data = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        // Assignable when it's active OR auto-dispatch gave up (no_driver_found).
        if (! $order->isActive() && $order->status !== 'no_driver_found') {
            return back()->with('error', 'This order can no longer be assigned.');
        }

        $newDriver = Driver::findOrFail($data['driver_id']);
        $oldDriverId = $order->driver_id;

        // No active driver yet (pending / scheduled / no_driver_found) → force-assign
        // straight to "accepted". Already-ongoing order → just swap the driver.
        $freshAssign = $oldDriverId === null
            || in_array($order->status, ['pending', 'scheduled', 'no_driver_found'], true);

        $updates = ['driver_id' => $newDriver->id, 'driver_assigned_at' => now()];
        if ($freshAssign) {
            $updates['status'] = 'accepted';
            $updates['accepted_at'] = now();
        }
        $order->update($updates);

        $this->dispatch->record($order, $freshAssign ? 'assigned' : 'reassigned', $newDriver->id,
            ['meta' => ['by' => 'admin', 'from_driver_id' => $oldDriverId, 'reason' => $data['reason'] ?? null]]);

        // Tell the customer (real-time + push) that a driver is now on the way.
        if ($freshAssign) {
            broadcast(new \App\Events\OrderAcceptedEvent($order->fresh(), $newDriver->loadMissing('activeVehicle')));
            if ($order->user_id) {
                $this->notify('user', $order->user_id, 'Driver assigned',
                    'A driver has been assigned to your order ' . $order->order_number . ' and is on the way.', 'order_update');
            }
        }

        if ($oldDriverId && $oldDriverId !== $newDriver->id) {
            $this->notify('driver', $oldDriverId, 'Trip reassigned',
                'Order ' . $order->order_number . ' has been reassigned to another driver.', 'order_update');
        }
        $this->notify('driver', $newDriver->id, 'New trip assigned',
            'You have been assigned a new order ' . $order->order_number . '.', 'order_request');

        return back()->with('success', 'Order ' . ($freshAssign ? 'assigned' : 'reassigned') . ' to ' . $newDriver->name . '.');
    }

    // Force a stuck order to completed (super admin only).
    public function forceComplete(Request $request, string $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($id);

        if (in_array($order->status, ['completed', 'cancelled', 'rejected'], true)) {
            return back()->with('error', 'This order is already finalised.');
        }

        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
            'cancellation_reason' => null,
        ]);

        if ($order->driver_id) {
            $this->notify('driver', $order->driver_id, 'Order completed',
                'Order ' . $order->order_number . ' has been completed by admin. Reason: ' . $data['reason'], 'order_update');
        }

        return back()->with('success', 'Order ' . $order->order_number . ' force-completed.');
    }

    // ---------------------------------------------------------------------

    // Notify a user/driver in-app and via push (FCM, when configured).
    private function notify(string $type, int $id, string $title, string $body, string $notifType): void
    {
        $this->notifications->sendPush($type, $id, $title, $body, $notifType);
    }
}
