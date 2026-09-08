<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VehicleCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ScheduledOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->whereNotNull('scheduled_at')
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($o) => [
                'order_id' => $o->id,
                'order_number' => $o->order_number,
                'type' => $o->type,
                'scheduled_at' => $o->scheduled_at?->toISOString(),
                'pickup_address' => $o->pickup_address,
                'drop_address' => $o->drop_address,
                'vehicle_category' => VehicleCategory::find($o->vehicle_category_id)?->name,
                'estimated_fare' => number_format((float) $o->total_amount, 2, '.', ''),
                'status' => $o->status,
            ]);

        return $this->success($orders, 'Scheduled orders fetched.');
    }

    public function cancel(Request $request, string $orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->where('status', 'scheduled')->first();
        if (! $order) {
            return $this->error('Scheduled order not found.', 404);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_by' => 'user',
            'cancellation_reason' => $request->input('reason', 'Cancelled by user'),
            'cancelled_at' => now(),
        ]);

        return $this->success(['cancellation_fee' => '0.00'], 'Scheduled order cancelled.');
    }
}
