<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class RevenueController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports,read'),
        ];
    }

    public function index(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfDay();
        $groupBy = in_array($request->query('group_by'), ['day', 'week', 'month'], true)
            ? $request->query('group_by')
            : 'day';

        // MySQL date bucket expression per grouping.
        $bucket = match ($groupBy) {
            'week' => "DATE_FORMAT(completed_at, '%x-W%v')",
            'month' => "DATE_FORMAT(completed_at, '%Y-%m')",
            default => "DATE(completed_at)",
        };

        $rows = Order::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->selectRaw("$bucket as bucket")
            ->selectRaw("SUM(CASE WHEN type = 'ride' THEN 1 ELSE 0 END) as rides")
            ->selectRaw("SUM(CASE WHEN type = 'parcel' THEN 1 ELSE 0 END) as parcels")
            ->selectRaw('SUM(total_amount) as gross')
            ->selectRaw('SUM(admin_commission) as commission')
            ->selectRaw('SUM(driver_earning) as driver_payouts')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        // Refunds per bucket (separate ledger).
        $refundBucket = match ($groupBy) {
            'week' => "DATE_FORMAT(created_at, '%x-W%v')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE(created_at)",
        };
        $refunds = WalletTransaction::query()
            ->where('category', 'refund')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("$refundBucket as bucket")
            ->selectRaw('SUM(amount) as refunds')
            ->groupBy('bucket')
            ->pluck('refunds', 'bucket');

        $breakdown = $rows->map(function ($r) use ($refunds) {
            $refund = (float) ($refunds[$r->bucket] ?? 0);
            return [
                'bucket' => $r->bucket,
                'rides' => (int) $r->rides,
                'parcels' => (int) $r->parcels,
                'gross' => (float) $r->gross,
                'commission' => (float) $r->commission,
                'refunds' => $refund,
                'net' => (float) $r->gross - $refund,
            ];
        });

        $totalRefunds = (float) $refunds->sum();
        $gross = (float) $rows->sum('gross');

        return view('admin.payments.revenue.index', [
            'breakdown' => $breakdown,
            'groupBy' => $groupBy,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'summary' => [
                'gross' => $gross,
                'commission' => (float) $rows->sum('commission'),
                'driver_payouts' => (float) $rows->sum('driver_payouts'),
                'refunds' => $totalRefunds,
                'net' => $gross - $totalRefunds,
            ],
            'chart' => [
                'labels' => $breakdown->pluck('bucket')->values(),
                'gross' => $breakdown->pluck('gross')->values(),
                'net' => $breakdown->pluck('net')->values(),
            ],
        ]);
    }
}
