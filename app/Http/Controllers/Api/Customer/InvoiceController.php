<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function show(Request $request, string $orderId)
    {
        $order = $this->order($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success($this->invoiceData($order), 'Invoice fetched.');
    }

    // Basic PDF: a print-optimised HTML page (browser → Save as PDF).
    public function pdf(Request $request, string $orderId)
    {
        $order = $this->order($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return response()
            ->view('invoices.order', ['inv' => $this->invoiceData($order)])
            ->header('Content-Type', 'text/html');
    }

    // ---------------------------------------------------------------------

    private function order(Request $request, string $orderId): ?Order
    {
        return Order::where('id', $orderId)->where('user_id', $request->user()->id)
            ->with(['driver.activeVehicle', 'user'])->first();
    }

    private function invoiceData(Order $order): array
    {
        $v = $order->driver?->activeVehicle;

        return [
            'invoice_number' => 'INV-' . $order->order_number,
            'order_number' => $order->order_number,
            'order_type' => $order->type,
            'date' => $order->created_at->toISOString(),
            'customer' => ['name' => $order->user->name ?? null, 'phone' => $order->user->phone ?? null],
            'driver' => $order->driver ? ['name' => $order->driver->name, 'vehicle' => $v ? "{$v->make} {$v->model}" : null] : null,
            'route' => [
                'from' => $order->pickup_address,
                'to' => $order->drop_address,
                'distance_km' => number_format((float) $order->distance_km, 2, '.', ''),
                'duration_minutes' => $order->duration_minutes,
            ],
            'fare_breakdown' => [
                'base_fare' => number_format((float) $order->base_fare, 2, '.', ''),
                'distance_charge' => number_format((float) $order->distance_charge, 2, '.', ''),
                'time_charge' => number_format((float) $order->time_charge, 2, '.', ''),
                'delivery_charge' => number_format((float) $order->delivery_charge, 2, '.', ''),
                'surge_amount' => number_format((float) $order->surge_amount, 2, '.', ''),
                'coupon_discount' => number_format((float) $order->coupon_discount, 2, '.', ''),
                'tip' => number_format((float) $order->tip_amount, 2, '.', ''),
                'total' => number_format((float) $order->total_amount, 2, '.', ''),
            ],
            'payment' => ['method' => $order->payment_method, 'status' => $order->payment_status],
        ];
    }
}
