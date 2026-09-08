<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class CustomerReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports,read'),
        ];
    }

    public function index(Request $request)
    {
        $from = ($request->date('from') ?? now()->startOfMonth())->format('Y-m-d');
        $to = ($request->date('to') ?? now()->endOfDay())->format('Y-m-d');

        $summary = Cache::remember('report_customers_' . md5("$from|$to"), 300, function () use ($from, $to) {
            $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

            $newCustomers = User::whereBetween('created_at', $range)->count();

            // Customers who placed at least one order in the window.
            $activeIds = Order::whereBetween('created_at', $range)->distinct()->pluck('user_id');
            $active = $activeIds->count();

            // Of those, how many have 2+ lifetime orders.
            $retained = User::whereIn('id', $activeIds)->has('orders', '>=', 2)->count();

            return [
                'new' => $newCustomers,
                'active' => $active,
                'retained' => $retained,
                'retention_rate' => $active > 0 ? round($retained / $active * 100, 1) : 0,
            ];
        });

        $topCustomers = User::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->withMax('orders as last_order_at', 'created_at')
            ->having('orders_count', '>', 0)
            ->orderByDesc('total_spent')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.customers', [
            'summary' => $summary,
            'customers' => $topCustomers,
            'filters' => compact('from', 'to'),
        ]);
    }
}
