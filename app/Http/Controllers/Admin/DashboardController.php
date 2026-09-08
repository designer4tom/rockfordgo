<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\Order;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->stats();
        $charts = $this->chartData();

        // Recent orders — eager load to avoid N+1.
        $recentOrders = Order::with(['user:id,name', 'driver:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        // Recent driver registrations.
        $recentDrivers = Driver::latest()
            ->limit(5)
            ->get(['id', 'name', 'phone', 'avatar', 'status', 'created_at']);

        // Documents expiring within 30 days.
        $expiringDocs = DriverDocument::with('driver:id,name')
            ->where('status', 'approved')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), today()->addDays(30)])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        // Maps key: admin-managed (system_settings) first, then .env fallback.
        $mapsKey = \App\Models\SystemSetting::get('google_maps_key') ?: config('services.google_maps.key');
        $mapCenter = [
            'lat' => mapCenter()['lat'],
            'lng' => mapCenter()['lng'],
            'zoom' => (int) \App\Models\SystemSetting::get('map_zoom', '12'),
        ];

        return view('admin.dashboard.index', compact(
            'stats', 'charts', 'recentOrders', 'recentDrivers', 'expiringDocs', 'mapsKey', 'mapCenter'
        ));
    }

    // JSON endpoint for periodic card refresh (no full page reload).
    public function statsJson()
    {
        return response()->json($this->stats());
    }

    // Live online-driver locations for the dashboard map (polled by the page).
    public function onlineDriversJson()
    {
        $drivers = Driver::where('is_online', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get(['id', 'name', 'phone', 'current_lat', 'current_lng'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'phone' => $d->phone,
                'lat' => (float) $d->current_lat,
                'lng' => (float) $d->current_lng,
            ]);

        return response()->json(['drivers' => $drivers]);
    }

    // ---------------------------------------------------------------------
    // Data builders
    // ---------------------------------------------------------------------

    // Aggregate dashboard counters (cached for 60 seconds).
    private function stats(): array
    {
        return Cache::remember('dashboard_stats', 60, function () {
            $today = today();
            $yesterday = today()->subDay();

            $completedRideToday = Order::where('type', 'ride')->where('status', 'completed')
                ->whereDate('created_at', $today)->count();
            $completedRideYesterday = Order::where('type', 'ride')->where('status', 'completed')
                ->whereDate('created_at', $yesterday)->count();

            $completedParcelToday = Order::where('type', 'parcel')->where('status', 'completed')
                ->whereDate('created_at', $today)->count();
            $completedParcelYesterday = Order::where('type', 'parcel')->where('status', 'completed')
                ->whereDate('created_at', $yesterday)->count();

            $revenueToday = (float) Order::where('status', 'completed')
                ->whereDate('created_at', $today)->sum('total_amount');
            $revenueYesterday = (float) Order::where('status', 'completed')
                ->whereDate('created_at', $yesterday)->sum('total_amount');

            return [
                // Today
                'today_rides' => $completedRideToday,
                'today_rides_change' => $this->percentChange($completedRideToday, $completedRideYesterday),
                'today_parcels' => $completedParcelToday,
                'today_parcels_change' => $this->percentChange($completedParcelToday, $completedParcelYesterday),
                'today_revenue' => $revenueToday,
                'today_revenue_change' => $this->percentChange($revenueToday, $revenueYesterday),
                'today_new_users' => User::whereDate('created_at', $today)->count(),

                // Overall
                'total_drivers' => Driver::count(),
                'active_drivers' => Driver::where('is_online', true)->count(),
                'total_users' => User::count(),
                'total_orders' => Order::where('status', 'completed')->count(),

                // Alerts
                'pending_approvals' => Driver::where('status', 'pending')->count(),
                'pending_disputes' => Dispute::whereIn('status', ['open', 'under_review'])->count(),
                'pending_withdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
                'active_sos' => SosAlert::whereIn('status', ['active', 'acknowledged'])->count(),
                'expiring_docs' => DriverDocument::where('status', 'approved')
                    ->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [today(), today()->addDays(30)])
                    ->count(),
            ];
        });
    }

    // Build the 7-day revenue / order / driver-status series for the charts.
    private function chartData(): array
    {
        $start = today()->subDays(6);

        // Revenue + order counts grouped by day and type.
        $rows = Order::where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw("SUM(CASE WHEN type='ride' THEN total_amount ELSE 0 END) as ride_revenue")
            ->selectRaw("SUM(CASE WHEN type='parcel' THEN total_amount ELSE 0 END) as parcel_revenue")
            ->selectRaw("SUM(CASE WHEN type='ride' THEN 1 ELSE 0 END) as ride_count")
            ->selectRaw("SUM(CASE WHEN type='parcel' THEN 1 ELSE 0 END) as parcel_count")
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $rideRevenue = $parcelRevenue = $rideCount = $parcelCount = [];

        foreach (CarbonPeriod::create($start, today()) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $row = $rows->get($key);

            $rideRevenue[] = $row ? (float) $row->ride_revenue : 0;
            $parcelRevenue[] = $row ? (float) $row->parcel_revenue : 0;
            $rideCount[] = $row ? (int) $row->ride_count : 0;
            $parcelCount[] = $row ? (int) $row->parcel_count : 0;
        }

        // Driver status distribution for the doughnut chart.
        $driverStatus = [
            'online' => Driver::where('is_online', true)->count(),
            'offline' => Driver::where('is_online', false)->where('status', 'approved')->count(),
            'suspended' => Driver::whereIn('status', ['suspended', 'blocked'])->count(),
            'pending' => Driver::where('status', 'pending')->count(),
        ];

        return [
            'labels' => $labels,
            'ride_revenue' => $rideRevenue,
            'parcel_revenue' => $parcelRevenue,
            'ride_count' => $rideCount,
            'parcel_count' => $parcelCount,
            'driver_status' => $driverStatus,
        ];
    }

    // Percentage change vs. the previous period (rounded).
    private function percentChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
