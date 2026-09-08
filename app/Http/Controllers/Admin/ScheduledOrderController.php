<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ScheduledOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders,read'),
        ];
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->whereNotNull('scheduled_at')
            ->whereIn('status', ['scheduled', 'pending'])
            ->where('scheduled_at', '>=', now()->subHour())
            ->with(['user:id,name,phone', 'service:id,name', 'driver:id,name'])
            ->orderBy('scheduled_at')
            ->paginate(20);

        return view('admin.orders.scheduled', compact('orders'));
    }
}
