<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SosController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:sos,read', only: ['index', 'show']),
            new Middleware('permission:sos,write', only: ['acknowledge', 'resolve']),
        ];
    }

    public function index(Request $request)
    {
        $alerts = SosAlert::with(['order:id,order_number', 'acknowledgedBy:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $this->hydrateTriggerers($alerts->getCollection());

        $active = SosAlert::where('status', 'active')->with('order:id,order_number')->latest()->get();
        $this->hydrateTriggerers($active);

        return view('admin.sos.index', [
            'alerts' => $alerts,
            'active' => $active,
            'mapsKey' => \App\Models\SystemSetting::get('google_maps_key'),
            'pusherKey' => \App\Models\SystemSetting::get('pusher_key'),
            'pusherCluster' => \App\Models\SystemSetting::get('pusher_cluster', 'mt1'),
        ]);
    }

    public function show(string $id)
    {
        $alert = SosAlert::with(['order.user', 'order.driver', 'acknowledgedBy:id,name'])->findOrFail($id);
        $triggerer = $this->resolveTriggerer($alert);

        return view('admin.sos.show', [
            'alert' => $alert,
            'triggerer' => $triggerer,
            'mapsKey' => \App\Models\SystemSetting::get('google_maps_key'),
        ]);
    }

    public function acknowledge(Request $request, string $id)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $alert = SosAlert::findOrFail($id);
        if ($alert->status !== 'active') {
            return back()->with('warning', 'This alert has already been handled.');
        }

        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => auth('admin')->id(),
            'acknowledged_at' => now(),
            'note' => $data['note'] ?? $alert->note,
        ]);

        return back()->with('success', 'SOS alert acknowledged.');
    }

    public function resolve(Request $request, string $id)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $alert = SosAlert::findOrFail($id);
        if ($alert->status === 'resolved') {
            return back()->with('warning', 'This alert is already resolved.');
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'note' => $data['note'],
            'acknowledged_by' => $alert->acknowledged_by ?? auth('admin')->id(),
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
        ]);

        return back()->with('success', 'SOS alert resolved.');
    }

    // ---------------------------------------------------------------------

    private function hydrateTriggerers($collection): void
    {
        $userIds = $collection->where('triggered_by', 'user')->pluck('triggered_by_id')->unique();
        $driverIds = $collection->where('triggered_by', 'driver')->pluck('triggered_by_id')->unique();

        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'phone'])->keyBy('id');
        $drivers = Driver::whereIn('id', $driverIds)->get(['id', 'name', 'phone'])->keyBy('id');

        foreach ($collection as $a) {
            $m = $a->triggered_by === 'driver' ? ($drivers[$a->triggered_by_id] ?? null) : ($users[$a->triggered_by_id] ?? null);
            $a->triggerer_name = $m->name ?? ('#' . $a->triggered_by_id);
            $a->triggerer_phone = $m->phone ?? null;
        }
    }

    private function resolveTriggerer(SosAlert $alert): ?array
    {
        $model = $alert->triggered_by === 'driver'
            ? Driver::find($alert->triggered_by_id)
            : User::find($alert->triggered_by_id);

        if (! $model) {
            return null;
        }

        return ['type' => $alert->triggered_by, 'name' => $model->name, 'phone' => $model->phone, 'id' => $model->id];
    }
}
