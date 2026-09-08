<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\VehicleCategory;
use App\Models\WalletTransaction;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports,read'),
        ];
    }

    public function index(Request $request)
    {
        $drivers = $this->query($request)->paginate(25)->withQueryString();
        $this->attachEarnings($drivers->getCollection());

        return view('admin.reports.drivers', [
            'drivers' => $drivers,
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::orderBy('name')->get(),
            'topByEarnings' => $this->topByEarnings(),
            'topByTrips' => Driver::orderByDesc('total_trips')->limit(10)->get(['id', 'name', 'total_trips']),
            'lowestRated' => Driver::where('average_rating', '<', 3)->where('total_trips', '>', 0)
                ->orderBy('average_rating')->limit(10)->get(['id', 'name', 'average_rating']),
            'filters' => $request->only(['zone_id', 'vehicle_category_id', 'status', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $drivers = $this->query($request)->get();
        $this->attachEarnings($drivers);

        return response()->streamDownload(function () use ($drivers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Driver', 'Zone', 'Total Trips', 'Total Earned', 'Commission Paid', 'Due', 'Avg Rating', 'Completion Rate']);
            foreach ($drivers as $d) {
                fputcsv($out, [$d->name, $d->zone->name ?? '', $d->total_trips, $d->total_earned, $d->commission_paid, $d->due_amount, $d->average_rating, $d->completion_rate]);
            }
            fclose($out);
        }, 'driver-report-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ---------------------------------------------------------------------

    private function query(Request $request)
    {
        return Driver::query()
            ->with('zone:id,name')
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->zone_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('vehicle_category_id'), fn ($q) => $q->whereHas('vehicles', fn ($v) => $v->where('vehicle_category_id', $request->vehicle_category_id)))
            ->orderByDesc('total_trips');
    }

    // Sum earnings + commission from the wallet ledger for a set of drivers.
    private function attachEarnings($collection): void
    {
        $ids = $collection->pluck('id');

        $earned = WalletTransaction::where('owner_type', 'driver')->whereIn('owner_id', $ids)
            ->whereIn('category', ['trip_earning', 'parcel_earning'])
            ->selectRaw('owner_id, SUM(amount) as t')->groupBy('owner_id')->pluck('t', 'owner_id');

        $commission = WalletTransaction::where('owner_type', 'driver')->whereIn('owner_id', $ids)
            ->where('category', 'commission')
            ->selectRaw('owner_id, SUM(amount) as t')->groupBy('owner_id')->pluck('t', 'owner_id');

        foreach ($collection as $d) {
            $d->total_earned = (float) ($earned[$d->id] ?? 0);
            $d->commission_paid = (float) ($commission[$d->id] ?? 0);
        }
    }

    private function topByEarnings()
    {
        $top = WalletTransaction::where('owner_type', 'driver')
            ->whereIn('category', ['trip_earning', 'parcel_earning'])
            ->selectRaw('owner_id, SUM(amount) as total')
            ->groupBy('owner_id')->orderByDesc('total')->limit(10)->get();

        $names = Driver::whereIn('id', $top->pluck('owner_id'))->pluck('name', 'id');

        return $top->map(fn ($r) => ['name' => $names[$r->owner_id] ?? '#' . $r->owner_id, 'total' => (float) $r->total]);
    }
}
