<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleCategoryController extends Controller implements HasMiddleware
{
    private const ACTIVE_ORDER_STATUSES = [
        'scheduled', 'pending', 'accepted', 'go_to_pickup', 'confirm_arrival',
        'picked_up', 'start_ride', 'dropped_off',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $serviceId = $request->integer('service_id') ?: null;

        $categories = VehicleCategory::with('service')
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        // Only ride-type services have vehicle categories.
        $rideServices = Service::where('type', 'ride')->orderBy('name')->get();

        return view('admin.vehicle-categories.index', compact('categories', 'rideServices', 'serviceId'));
    }

    public function create()
    {
        return view('admin.vehicle-categories.create', [
            'rideServices' => Service::where('type', 'ride')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['icon'] = $this->storeIcon($request);
        $data['is_active'] = $request->boolean('is_active');

        VehicleCategory::create($data);

        return redirect()->route('admin.vehicle-categories.index')->with('success', 'Vehicle category created.');
    }

    public function edit(string $id)
    {
        $category = VehicleCategory::findOrFail($id);

        return view('admin.vehicle-categories.edit', [
            'category' => $category,
            'rideServices' => Service::where('type', 'ride')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $category = VehicleCategory::findOrFail($id);
        $data = $this->validateData($request);

        if ($newIcon = $this->storeIcon($request)) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $newIcon;
        }

        $data['is_active'] = $request->boolean('is_active');
        $category->update($data);

        return redirect()->route('admin.vehicle-categories.index')->with('success', 'Vehicle category updated.');
    }

    public function destroy(string $id)
    {
        $category = VehicleCategory::findOrFail($id);

        $hasActive = Order::where('vehicle_category_id', $category->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();

        if ($hasActive) {
            return back()->with('error', 'এই Category-তে active orders আছে, delete করা যাবে না।');
        }

        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return back()->with('success', 'Vehicle category deleted.');
    }

    public function toggleStatus(string $id)
    {
        $category = VehicleCategory::findOrFail($id);
        $category->is_active = ! $category->is_active;
        $category->save();

        return back()->with('success', 'Status updated for ' . $category->name . '.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request): array
    {
        return $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'per_km_rate' => ['required', 'numeric', 'min:0'],
            'per_minute_rate' => ['required', 'numeric', 'min:0'],
            'minimum_fare' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function storeIcon(Request $request): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        $ext = $request->file('icon')->getClientOriginalExtension();

        return $request->file('icon')->storeAs('vehicle-categories', Str::random(12) . '.' . $ext, 'public');
    }
}
