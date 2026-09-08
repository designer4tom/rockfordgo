<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Service;
use App\Models\Zone;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ZoneController extends Controller implements HasMiddleware
{
    private const ACTIVE_ORDER_STATUSES = [
        'scheduled', 'pending', 'accepted', 'go_to_pickup', 'confirm_arrival',
        'picked_up', 'start_ride', 'dropped_off',
    ];

    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index', 'show']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $zones = Zone::with('services')
            ->withCount('drivers')
            ->withCount(['drivers as online_drivers_count' => fn ($q) => $q->where('is_online', true)])
            ->orderBy('name')
            ->paginate(20);

        // Zone-wise order + revenue analytics (today) — drivers are mapped to a
        // zone by their auto-detected current zone_id.
        $stats = Order::query()
            ->join('drivers', 'orders.driver_id', '=', 'drivers.id')
            ->whereIn('drivers.zone_id', $zones->pluck('id'))
            ->whereDate('orders.created_at', today())
            ->selectRaw('drivers.zone_id as zid')
            ->selectRaw('COUNT(*) as orders_today')
            ->selectRaw('SUM(CASE WHEN orders.status = "completed" THEN orders.total_amount ELSE 0 END) as revenue_today')
            ->groupBy('drivers.zone_id')
            ->get()
            ->keyBy('zid');

        return view('admin.zones.index', compact('zones', 'stats'));
    }

    public function create()
    {
        return view('admin.zones.create', [
            'zone' => new Zone(['is_active' => true]),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'zoneServices' => [],
            'mapsKey' => $this->settings->get('google_maps_key') ?: config('services.google_maps.key'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($request, $data) {
            $zone = Zone::create($this->zoneAttributes($request, $data));
            $this->syncServices($zone, $request);
        });

        app(\App\Services\ZoneService::class)->forgetCache();

        return redirect()->route('admin.zones.index')->with('success', 'Zone created.');
    }

    public function show(string $id)
    {
        $zone = Zone::with('services')->withCount('drivers')->findOrFail($id);

        // Online drivers currently assigned to this zone.
        $onlineDrivers = Driver::where('zone_id', $zone->id)
            ->where('is_online', true)
            ->get(['id', 'name', 'current_lat', 'current_lng']);

        $tripsToday = Order::whereHas('driver', fn ($q) => $q->where('zone_id', $zone->id))
            ->whereDate('created_at', today())
            ->count();

        return view('admin.zones.show', [
            'zone' => $zone,
            'onlineDrivers' => $onlineDrivers,
            'tripsToday' => $tripsToday,
            'mapsKey' => $this->settings->get('google_maps_key') ?: config('services.google_maps.key'),
        ]);
    }

    public function edit(string $id)
    {
        $zone = Zone::with('services')->findOrFail($id);

        // Pivot data keyed by service_id for pre-filling operating hours.
        $zoneServices = $zone->services->mapWithKeys(fn ($s) => [
            $s->id => [
                'start' => $s->pivot->operating_hours_start,
                'end' => $s->pivot->operating_hours_end,
            ],
        ])->toArray();

        return view('admin.zones.edit', [
            'zone' => $zone,
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'zoneServices' => $zoneServices,
            'mapsKey' => $this->settings->get('google_maps_key') ?: config('services.google_maps.key'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $zone = Zone::findOrFail($id);
        $data = $this->validateData($request);

        DB::transaction(function () use ($request, $data, $zone) {
            $zone->update($this->zoneAttributes($request, $data));
            $this->syncServices($zone, $request);
        });

        app(\App\Services\ZoneService::class)->forgetCache();

        return redirect()->route('admin.zones.index')->with('success', 'Zone updated.');
    }

    public function destroy(string $id)
    {
        $zone = Zone::findOrFail($id);

        $hasPendingOrders = Order::whereHas('driver', fn ($q) => $q->where('zone_id', $zone->id))
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();

        if ($hasPendingOrders) {
            return back()->with('error', 'এই Zone-এ pending orders আছে, এখন delete করা যাবে না।');
        }

        DB::transaction(function () use ($zone) {
            // Detach drivers (set zone_id null) then delete.
            Driver::where('zone_id', $zone->id)->update(['zone_id' => null]);
            $zone->delete();
        });

        app(\App\Services\ZoneService::class)->forgetCache();

        return back()->with('success', 'Zone deleted.');
    }

    public function toggleStatus(string $id)
    {
        $zone = Zone::findOrFail($id);
        $zone->is_active = ! $zone->is_active;
        $zone->save();

        app(\App\Services\ZoneService::class)->forgetCache();

        return back()->with('success', 'Status updated for ' . $zone->name . '.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'polygon' => ['nullable', 'string'],
            'center_lat' => ['nullable', 'numeric'],
            'center_lng' => ['nullable', 'numeric'],
            'radius_km' => ['nullable', 'numeric', 'min:0'],
            'shape' => ['nullable', 'in:draw,pin'],
            'services' => ['nullable', 'array'],
            'services.*' => ['exists:services,id'],
        ]);

        // Either a drawn area or a pin with a radius. Without one of the two the
        // zone exists but can never match a pickup, which looks like a dispatch
        // bug rather than a half-filled form.
        $hasPolygon = ! empty($validated['polygon']) && $validated['polygon'] !== '[]';
        $hasCircle = ! empty($validated['radius_km'])
            && (float) $validated['radius_km'] > 0
            && isset($validated['center_lat'], $validated['center_lng']);

        if (! $hasPolygon && ! $hasCircle) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'polygon' => __('admin.zone_geometry_required'),
            ]);
        }

        return $validated;
    }

    // Build the zone column values (decoding the polygon JSON safely).
    private function zoneAttributes(Request $request, array $data): array
    {
        $polygon = [];
        if (! empty($data['polygon'])) {
            $decoded = json_decode($data['polygon'], true);
            $polygon = is_array($decoded) ? $decoded : [];
        }

        return [
            'name' => $data['name'],
            'polygon' => $polygon,
            'center_lat' => $data['center_lat'] ?? 0,
            'center_lng' => $data['center_lng'] ?? 0,
            'radius_km' => $data['radius_km'] ?? 0,
            // Which editor produced this geometry, so reopening it shows the
            // same thing the admin drew rather than a guess.
            'shape' => $data['shape'] ?? 'draw',
            'is_active' => $request->boolean('is_active'),
        ];
    }

    // Replace the zone's service assignments with submitted operating hours.
    private function syncServices(Zone $zone, Request $request): void
    {
        $serviceIds = $request->input('services', []);
        $hours = $request->input('operating_hours', []);

        $sync = [];
        foreach ($serviceIds as $serviceId) {
            $sync[$serviceId] = [
                'operating_hours_start' => $hours[$serviceId]['start'] ?? null,
                'operating_hours_end' => $hours[$serviceId]['end'] ?? null,
            ];
        }

        $zone->services()->sync($sync);
    }
}
