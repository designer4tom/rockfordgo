<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports,read'),
        ];
    }

    public function index(Request $request)
    {
        $params = $this->params($request);
        $data = $this->report($params);
        // Re-wrap the cached plain array so the Blade can use Collection methods.
        $data['breakdown'] = collect($data['breakdown']);

        return view('admin.reports.revenue', array_merge($data, [
            'zones' => Zone::orderBy('name')->get(),
            'filters' => $params,
        ]));
    }

    // Basic PDF: a print-optimised HTML page that opens the browser print dialog.
    public function pdf(Request $request)
    {
        $params = $this->params($request);

        return view('admin.reports.revenue-pdf', array_merge($this->report($params), [
            'filters' => $params,
            'currency' => \App\Models\SystemSetting::get('currency', 'BDT'),
            'generatedAt' => now()->format('d M Y, H:i'),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->report($this->params($request));

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Period', 'Rides', 'Parcels', 'Gross', 'Commission', 'Refunds', 'Net']);
            foreach ($data['breakdown'] as $row) {
                fputcsv($out, [$row['bucket'], $row['rides'], $row['parcels'], $row['gross'], $row['commission'], $row['refunds'], $row['net']]);
            }
            fclose($out);
        }, 'revenue-report-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ---------------------------------------------------------------------

    private function params(Request $request): array
    {
        return [
            'from' => ($request->date('from') ?? now()->startOfMonth())->format('Y-m-d'),
            'to' => ($request->date('to') ?? now()->endOfDay())->format('Y-m-d'),
            'group_by' => in_array($request->query('group_by'), ['daily', 'weekly', 'monthly'], true) ? $request->query('group_by') : 'daily',
            'service_type' => in_array($request->query('service_type'), ['ride', 'parcel'], true) ? $request->query('service_type') : '',
            'zone_id' => $request->query('zone_id') ?: '',
            'payment_method' => in_array($request->query('payment_method'), ['cash', 'online', 'wallet', 'cod'], true) ? $request->query('payment_method') : '',
        ];
    }

    // Cached for 5 minutes (rule #1: heavy report queries are cached).
    private function report(array $p): array
    {
        return Cache::remember('report_revenue_' . md5(json_encode($p)), 300, function () use ($p) {
            $bucket = match ($p['group_by']) {
                'weekly' => "DATE_FORMAT(completed_at, '%x-W%v')",
                'monthly' => "DATE_FORMAT(completed_at, '%Y-%m')",
                default => "DATE(completed_at)",
            };

            $base = Order::query()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$p['from'] . ' 00:00:00', $p['to'] . ' 23:59:59'])
                ->when($p['service_type'], fn ($q) => $q->where('type', $p['service_type']))
                ->when($p['payment_method'], fn ($q) => $q->where('payment_method', $p['payment_method']))
                ->when($p['zone_id'], fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('zone_id', $p['zone_id'])));

            $rows = (clone $base)
                ->selectRaw("$bucket as bucket")
                ->selectRaw("SUM(CASE WHEN type='ride' THEN 1 ELSE 0 END) as rides")
                ->selectRaw("SUM(CASE WHEN type='parcel' THEN 1 ELSE 0 END) as parcels")
                ->selectRaw("SUM(CASE WHEN type='ride' THEN total_amount ELSE 0 END) as ride_gross")
                ->selectRaw("SUM(CASE WHEN type='parcel' THEN total_amount ELSE 0 END) as parcel_gross")
                ->selectRaw('SUM(total_amount) as gross')
                ->selectRaw('SUM(admin_commission) as commission')
                ->selectRaw('SUM(driver_earning) as driver_payouts')
                ->groupBy('bucket')->orderBy('bucket')->get();

            $refundBucket = match ($p['group_by']) {
                'weekly' => "DATE_FORMAT(created_at, '%x-W%v')",
                'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => "DATE(created_at)",
            };
            $refunds = WalletTransaction::where('category', 'refund')
                ->whereBetween('created_at', [$p['from'] . ' 00:00:00', $p['to'] . ' 23:59:59'])
                ->selectRaw("$refundBucket as bucket")->selectRaw('SUM(amount) as r')
                ->groupBy('bucket')->pluck('r', 'bucket');

            $breakdown = $rows->map(fn ($r) => [
                'bucket' => $r->bucket,
                'rides' => (int) $r->rides,
                'parcels' => (int) $r->parcels,
                'ride_gross' => (float) $r->ride_gross,
                'parcel_gross' => (float) $r->parcel_gross,
                'gross' => (float) $r->gross,
                'commission' => (float) $r->commission,
                'refunds' => (float) ($refunds[$r->bucket] ?? 0),
                'net' => (float) $r->gross - (float) ($refunds[$r->bucket] ?? 0),
            ]);

            $gross = (float) $rows->sum('gross');
            $totalRefunds = (float) $refunds->sum();

            // Return PLAIN arrays only — caching Eloquent/Collection objects breaks
            // on deserialize ("incomplete object"). Views re-wrap with collect().
            return [
                'breakdown' => $breakdown->all(),
                'summary' => [
                    'gross' => $gross,
                    'commission' => (float) $rows->sum('commission'),
                    'driver_payouts' => (float) $rows->sum('driver_payouts'),
                    'net' => $gross - $totalRefunds,
                ],
                'chart' => [
                    'labels' => $breakdown->pluck('bucket')->values()->all(),
                    'ride' => $breakdown->pluck('ride_gross')->values()->all(),
                    'parcel' => $breakdown->pluck('parcel_gross')->values()->all(),
                ],
            ];
        });
    }
}
