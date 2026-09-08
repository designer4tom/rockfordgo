<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DriverEarningsController extends Controller
{
    use ApiResponse;

    // Aggregate earnings for a period (today / week / month / all).
    // Earnings come from the orders table (driver_earning), so they count BOTH
    // cash and online trips — cash trips never create a wallet credit, which is
    // why the old wallet-based query returned 0.
    public function index(Request $request)
    {
        $period = in_array($request->query('period'), ['today', 'week', 'month', 'all'], true) ? $request->query('period') : 'today';
        $driverId = $request->user()->id;

        $base = $this->completedQuery($driverId, $period);

        $rideEarn = (float) (clone $base)->where('type', 'ride')->sum('driver_earning');
        $parcelEarn = (float) (clone $base)->where('type', 'parcel')->sum('driver_earning');
        $commission = (float) (clone $base)->sum('admin_commission');
        $tips = (float) (clone $base)->sum('tip_amount');
        $trips = (clone $base)->count();

        $from = $this->periodStart($period);
        $minutes = (int) $request->user()->shiftLogs()
            ->when($from, fn ($q) => $q->where('went_online_at', '>=', $from))
            ->sum('total_minutes');

        return $this->success([
            'period' => $period,
            'total_earning' => number_format($rideEarn + $parcelEarn, 2, '.', ''),
            'total_trips' => $trips,
            'ride_earning' => number_format($rideEarn, 2, '.', ''),
            'parcel_earning' => number_format($parcelEarn, 2, '.', ''),
            'commission_paid' => number_format($commission, 2, '.', ''),
            'tips_received' => number_format($tips, 2, '.', ''),
            'bonus_earning' => '0.00', // no bonus system yet — app may show or hide
            'online_hours' => number_format($minutes / 60, 1, '.', ''),
        ], 'Earnings fetched.');
    }

    public function summary(Request $request)
    {
        $driverId = $request->user()->id;

        $block = function (string $period) use ($driverId) {
            $q = $this->completedQuery($driverId, $period);

            return [
                'earning' => number_format((float) (clone $q)->sum('driver_earning'), 2, '.', ''),
                'trips' => (clone $q)->count(),
            ];
        };

        return $this->success([
            'today' => $block('today'),
            'this_week' => $block('week'),
            'this_month' => $block('month'),
            'lifetime' => $block('all'),
        ], 'Earnings summary fetched.');
    }

    // Daily breakdown for the chart (week / month) — uses driver_earning.
    public function chart(Request $request)
    {
        $period = $request->query('period') === 'month' ? 'month' : 'week';
        $from = $this->periodStart($period);
        $driverId = $request->user()->id;

        $earnByDay = Order::where('driver_id', $driverId)->where('status', 'completed')
            ->where('completed_at', '>=', $from)
            ->selectRaw('DATE(completed_at) as d, SUM(driver_earning) as earning')
            ->groupBy('d')->pluck('earning', 'd');

        $tripsByDay = Order::where('driver_id', $driverId)->where('status', 'completed')
            ->where('completed_at', '>=', $from)
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $days = collect();
        for ($date = $from->copy(); $date <= now(); $date->addDay()) {
            $key = $date->toDateString();
            $days->push([
                'date' => $key,
                'earning' => number_format((float) ($earnByDay[$key] ?? 0), 2, '.', ''),
                'trips' => (int) ($tripsByDay[$key] ?? 0),
            ]);
        }

        return $this->success($days->values(), 'Earnings chart fetched.');
    }

    // ---------------------------------------------------------------------

    private function completedQuery(int $driverId, string $period): Builder
    {
        $from = $this->periodStart($period);

        return Order::where('driver_id', $driverId)
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('completed_at', '>=', $from));
    }

    private function periodStart(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'today' => today(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => null, // all
        };
    }
}
