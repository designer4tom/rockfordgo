<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurgePricingRule;
use App\Models\VehicleCategory;
use App\Models\Zone;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SurgePricingController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus', 'manualActivate', 'toggleMaster']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $rules = SurgePricingRule::with(['zone', 'vehicleCategory'])
            ->orderByDesc('is_manual')
            ->latest()
            ->paginate(20);

        return view('admin.surge-pricing.index', [
            'rules' => $rules,
            'surgeEnabled' => $this->settings->getBool('surge_enabled', false),
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::with('service')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.surge-pricing.create', [
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::with('service')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        $rule = SurgePricingRule::create($data);

        return redirect()->route('admin.surge-pricing.index')
            ->with('success', 'Surge rule created.')
            ->with($this->conflictFlash($rule));
    }

    public function edit(string $id)
    {
        $rule = SurgePricingRule::findOrFail($id);

        return view('admin.surge-pricing.edit', [
            'rule' => $rule,
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::with('service')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $rule = SurgePricingRule::findOrFail($id);
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        $rule->update($data);

        return redirect()->route('admin.surge-pricing.index')
            ->with('success', 'Surge rule updated.')
            ->with($this->conflictFlash($rule));
    }

    public function destroy(string $id)
    {
        SurgePricingRule::findOrFail($id)->delete();

        return back()->with('success', 'Surge rule deleted.');
    }

    public function toggleStatus(string $id)
    {
        $rule = SurgePricingRule::findOrFail($id);
        $rule->is_active = ! $rule->is_active;
        $rule->save();

        return back()->with('success', 'Status updated.');
    }

    // Master on/off switch for the entire surge system.
    public function toggleMaster(Request $request)
    {
        $enabled = ! $this->settings->getBool('surge_enabled', false);
        $this->settings->set('surge_enabled', $enabled ? 'true' : 'false', 'ride');

        return back()->with('success', 'Surge is now ' . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    // Create a temporary, auto-expiring surge rule.
    public function manualActivate(Request $request)
    {
        $data = $request->validate([
            'zone_id' => ['required', 'exists:zones,id'],
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
            'multiplier' => ['required', 'numeric', 'min:1.1', 'max:5.0'],
            'ends_at' => ['required', 'date', 'after:now'],
        ]);

        SurgePricingRule::create([
            'name' => 'Emergency Surge — ' . now()->format('d M H:i'),
            'zone_id' => $data['zone_id'],
            'vehicle_category_id' => $data['vehicle_category_id'] ?? null,
            'day_of_week' => null,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'multiplier' => $data['multiplier'],
            'is_manual' => true,
            'ends_at' => $data['ends_at'],
            'is_active' => true,
        ]);

        // Ensure the master switch is on so the manual surge takes effect.
        if (! $this->settings->getBool('surge_enabled', false)) {
            $this->settings->set('surge_enabled', 'true', 'ride');
        }

        return back()->with('success', 'Emergency surge activated until ' . $data['ends_at'] . '.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
            'day_of_week' => ['nullable', 'array'],
            'day_of_week.*' => ['integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'multiplier' => ['required', 'numeric', 'min:1.1', 'max:5.0'],
        ]);
    }

    // Warn (don't block) when an overlapping rule exists for the same scope.
    private function conflictFlash(SurgePricingRule $rule): array
    {
        $exists = SurgePricingRule::where('id', '!=', $rule->id)
            ->where('is_active', true)
            ->where('is_manual', false)
            ->where('zone_id', $rule->zone_id)
            ->where('vehicle_category_id', $rule->vehicle_category_id)
            ->where('start_time', '<', $rule->end_time)
            ->where('end_time', '>', $rule->start_time)
            ->exists();

        return $exists
            ? ['warning' => 'একই zone ও সময়ে আরেকটি surge rule আছে — overlap হতে পারে।']
            : [];
    }
}
