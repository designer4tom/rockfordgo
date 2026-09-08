<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DriverShiftController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $driver = $request->user();

        $shifts = $driver->shiftLogs()->latest('went_online_at')->paginate((int) $request->query('per_page', 20));

        $items = $shifts->getCollection()->map(function ($s) use ($driver) {
            $end = $s->went_offline_at ?? now();

            // Trips completed + earnings during this shift window.
            $trips = Order::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$s->went_online_at, $end])
                ->count();
            $earning = (float) WalletTransaction::where('owner_type', 'driver')->where('owner_id', $driver->id)
                ->whereIn('category', ['trip_earning', 'parcel_earning'])
                ->whereBetween('created_at', [$s->went_online_at, $end])
                ->sum('amount');

            return [
                'date' => $s->went_online_at->toDateString(),
                'went_online' => $s->went_online_at->format('H:i'),
                'went_offline' => $s->went_offline_at?->format('H:i'),
                'total_hours' => number_format(($s->total_minutes ?? 0) / 60, 1, '.', ''),
                'trips' => $trips,
                'earning' => number_format($earning, 2, '.', ''),
            ];
        })->all();

        return $this->paginated($shifts, $items, 'Shifts fetched.');
    }
}
