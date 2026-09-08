<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderHistoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->when(in_array($request->query('type'), ['ride', 'parcel'], true), fn($q) => $q->where('type', $request->query('type')))
            ->when($request->query('status') === 'completed', fn($q) => $q->where('status', 'completed'))
            ->when($request->query('status') === 'cancelled', fn($q) => $q->where('status', 'cancelled'))
            ->with(['driver:id,name,avatar', 'service:id,name,icon'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        $items = $orders->getCollection()->map(fn($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'type' => $o->type,
            'status' => $o->status,
            'service_name' => $o->service->name ?? null,
            'service_icon' => $o->service?->icon ? asset(Storage::url($o->service->icon)) : null,
            'pickup_address' => $o->pickup_address,
            'drop_address' => $o->drop_address,
            'total_amount' => number_format((float) $o->total_amount, 2, '.', ''),
            'payment_method' => $o->payment_method,
            'driver_name' => $o->driver->name ?? null,
            'driver_avatar' => $o->driver?->avatar ? asset(Storage::url($o->driver->avatar)) : null,
            'created_at' => $o->created_at->toISOString(),
            'completed_at' => $o->completed_at?->toISOString(),
        ])->all();

        return $this->paginated($orders, $items, 'Orders fetched.');
    }

    public function show(Request $request, string $id)
    {
        $order = Order::where('id', $id)->where('user_id', $request->user()->id)
            ->with(['driver.activeVehicle.vehicleCategory', 'service', 'ratings'])
            ->first();

        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success($this->detail($order), 'Order details fetched.');
    }

    private function detail(Order $order): array
    {
        $v = $order->driver?->activeVehicle;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'status' => $order->status,
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
            'distance_km' => number_format((float) $order->distance_km, 2, '.', ''),
            'duration_minutes' => $order->duration_minutes,
            'fare' => [
                'base_fare' => number_format((float) $order->base_fare, 2, '.', ''),
                'distance_charge' => number_format((float) $order->distance_charge, 2, '.', ''),
                'time_charge' => number_format((float) $order->time_charge, 2, '.', ''),
                'delivery_charge' => number_format((float) $order->delivery_charge, 2, '.', ''),
                'surge_amount' => number_format((float) $order->surge_amount, 2, '.', ''),
                'coupon_discount' => number_format((float) $order->coupon_discount, 2, '.', ''),
                'tip' => number_format((float) $order->tip_amount, 2, '.', ''),
                'total' => number_format((float) $order->total_amount, 2, '.', ''),
            ],
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'driver' => $order->driver ? [
                'name' => $order->driver->name,
                'phone' => $order->driver->phone,
                'rating' => (float) $order->driver->average_rating,
                'vehicle' => $v ? "{$v->make} {$v->model}" : null,
            ] : null,
            'created_at' => $order->created_at->toISOString(),
            'completed_at' => $order->completed_at?->toISOString(),
        ];
    }
}
