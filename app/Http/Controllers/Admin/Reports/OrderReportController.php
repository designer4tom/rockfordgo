<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

class OrderReportController extends Controller implements HasMiddleware
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
        $type = in_array($request->query('type'), ['ride', 'parcel'], true) ? $request->query('type') : '';
        $zoneId = $request->query('zone_id') ?: '';

        $data = Cache::remember('report_orders_' . md5("$from|$to|$type|$zoneId"), 300, function () use ($from, $to, $type, $zoneId) {
            $base = Order::query()
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($zoneId, fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('zone_id', $zoneId)));

            $total = (clone $base)->count();
            $completed = (clone $base)->where('status', 'completed')->count();
            $cancelled = (clone $base)->where('status', 'cancelled')->count();

            // Cache PLAIN arrays/stdClass only — never Eloquent collections/models,
            // which can deserialize as __PHP_Incomplete_Class from the cache store.
            $byStatus = (clone $base)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
            $byHour = (clone $base)->selectRaw('HOUR(created_at) as h, COUNT(*) as c')->groupBy('h')->pluck('c', 'h')->all();
            $byDow = (clone $base)->selectRaw('DAYOFWEEK(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd')->all();

            $daily = (clone $base)->selectRaw('DATE(created_at) as d')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed")
                ->selectRaw("SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled")
                ->selectRaw('AVG(total_amount) as avg_amount')
                ->selectRaw('AVG(distance_km) as avg_distance')
                ->groupBy('d')->orderBy('d')->get()
                ->map(fn ($r) => [
                    'd' => $r->d,
                    'total' => (int) $r->total,
                    'completed' => (int) $r->completed,
                    'cancelled' => (int) $r->cancelled,
                    'avg_amount' => (float) $r->avg_amount,
                    'avg_distance' => (float) $r->avg_distance,
                ])->all();

            $cancelledBy = (clone $base)->where('status', 'cancelled')
                ->selectRaw('cancelled_by, COUNT(*) as c')->groupBy('cancelled_by')->pluck('c', 'cancelled_by')->all();

            $reasons = (clone $base)->where('status', 'cancelled')->whereNotNull('cancellation_reason')
                ->selectRaw('cancellation_reason, COUNT(*) as c')->groupBy('cancellation_reason')
                ->orderByDesc('c')->limit(5)->pluck('c', 'cancellation_reason')->all();

            return compact('total', 'completed', 'cancelled', 'byStatus', 'byHour', 'byDow', 'daily', 'cancelledBy', 'reasons');
        });

        return view('admin.reports.orders', array_merge($data, [
            'zones' => Zone::orderBy('name')->get(),
            'cancellationRate' => $data['total'] > 0 ? round($data['cancelled'] / $data['total'] * 100, 1) : 0,
            'filters' => compact('from', 'to', 'type', 'zoneId'),
        ]));
    }
}
