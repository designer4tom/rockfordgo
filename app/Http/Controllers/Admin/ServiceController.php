<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller implements HasMiddleware
{
    // Order statuses that count as "active" (block deletion).
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

    public function index()
    {
        $services = Service::orderBy('sort_order')->paginate(20);

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['icon'] = $this->storeIcon($request, $data['slug']);
        $data['is_active'] = $request->boolean('is_active');

        Service::create($data);

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function edit(string $id)
    {
        $service = Service::findOrFail($id);

        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);
        $data = $this->validateData($request, $service);

        // Type cannot change on edit.
        unset($data['type']);

        if ($newIcon = $this->storeIcon($request, $data['slug'])) {
            if ($service->icon) {
                Storage::disk('public')->delete($service->icon);
            }
            $data['icon'] = $newIcon;
        }

        $data['is_active'] = $request->boolean('is_active');

        $service->update($data);

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function destroy(string $id)
    {
        $service = Service::findOrFail($id);

        if ($this->hasActiveOrders($service->id)) {
            return back()->with('error', 'এই Service-এ active orders আছে, delete করা যাবে না।');
        }

        if ($service->icon) {
            Storage::disk('public')->delete($service->icon);
        }

        $service->delete();

        return back()->with('success', 'Service deleted.');
    }

    public function toggleStatus(string $id)
    {
        $service = Service::findOrFail($id);
        $service->is_active = ! $service->is_active;
        $service->save();

        return back()->with('success', 'Status updated for ' . $service->name . '.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', Rule::unique('services', 'slug')->ignore($service?->id)],
            'type' => ['required', Rule::in(['ride', 'parcel'])],
            'icon' => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    // Store an uploaded icon and return its relative path (or null).
    private function storeIcon(Request $request, string $slug): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        $ext = $request->file('icon')->getClientOriginalExtension();

        return $request->file('icon')->storeAs('services', $slug . '-' . Str::random(6) . '.' . $ext, 'public');
    }

    private function hasActiveOrders(int $serviceId): bool
    {
        return Order::where('service_id', $serviceId)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();
    }
}
