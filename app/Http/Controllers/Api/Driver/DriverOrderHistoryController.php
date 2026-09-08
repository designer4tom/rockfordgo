<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DriverOrderHistoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = Order::where('driver_id', $request->user()->id)
            ->when(in_array($request->query('type'), ['ride', 'parcel'], true), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->query('status') === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($request->query('status') === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        $items = $orders->getCollection()->map(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'type' => $o->type,
            'status' => $o->status,
            'pickup_address' => $o->pickup_address,
            'drop_address' => $o->drop_address,
            'customer_name' => $o->user->name ?? null,
            'customer_image' => $o->user?->avatar ? asset(\Illuminate\Support\Facades\Storage::url($o->user->avatar)) : null,
            'driver_earning' => number_format((float) $o->driver_earning, 2, '.', ''),
            'total_amount' => number_format((float) $o->total_amount, 2, '.', ''),
            'created_at' => $o->created_at->toISOString(),
            'completed_at' => $o->completed_at?->toISOString(),
        ])->all();

        return $this->paginated($orders, $items, 'Orders fetched.');
    }

    public function show(Request $request, string $id)
    {
        $order = Order::where('id', $id)->where('driver_id', $request->user()->id)
            ->with('user:id,name,phone,avatar')->first();

        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'status' => $order->status,
            'customer' => [
                'name' => $order->user->name ?? null,
                'phone' => $order->user->phone ?? null,
                'image' => $order->user?->avatar ? asset(\Illuminate\Support\Facades\Storage::url($order->user->avatar)) : null,
            ],
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
            'driver_earning' => number_format((float) $order->driver_earning, 2, '.', ''),
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'payment_method' => $order->payment_method,
            'is_cod' => (bool) $order->is_cod,
            'cod_amount' => number_format((float) ($order->cod_amount ?? 0), 2, '.', ''),
            // Parcel delivery proof — which proof the driver must collect on completion.
            'proof_required' => $order->type === 'parcel' && \App\Models\SystemSetting::get('proof_of_delivery_enabled', 'true') !== 'false',
            'proof_type' => $order->type === 'parcel' ? ($order->proof_type ?: 'any') : null,
            'proof_collected' => $order->proof_collected_at !== null,
            'created_at' => $order->created_at->toISOString(),
            'completed_at' => $order->completed_at?->toISOString(),
        ], 'Order details fetched.');
    }
}
