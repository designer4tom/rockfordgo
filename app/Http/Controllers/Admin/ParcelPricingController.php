<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParcelPricing;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ParcelPricingController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus', 'saveCommission']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $pricings = ParcelPricing::orderBy('parcel_type')->orderBy('min_weight')->paginate(20);

        // Commission moved to Business Settings.
        return view('admin.parcel-pricing.index', compact('pricings'));
    }

    public function create()
    {
        return view('admin.parcel-pricing.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        ParcelPricing::create($data);

        return redirect()->route('admin.parcel-pricing.index')->with('success', 'Parcel pricing created.');
    }

    public function edit(string $id)
    {
        $pricing = ParcelPricing::findOrFail($id);

        return view('admin.parcel-pricing.edit', compact('pricing'));
    }

    public function update(Request $request, string $id)
    {
        $pricing = ParcelPricing::findOrFail($id);
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        $pricing->update($data);

        return redirect()->route('admin.parcel-pricing.index')->with('success', 'Parcel pricing updated.');
    }

    public function destroy(string $id)
    {
        ParcelPricing::findOrFail($id)->delete();

        return back()->with('success', 'Parcel pricing deleted.');
    }

    public function toggleStatus(string $id)
    {
        $pricing = ParcelPricing::findOrFail($id);
        $pricing->is_active = ! $pricing->is_active;
        $pricing->save();

        return back()->with('success', 'Status updated.');
    }

    // Save the admin commission percentages for ride and parcel.
    public function saveCommission(Request $request)
    {
        $data = $request->validate([
            'parcel_admin_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'ride_admin_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->settings->set('parcel_admin_commission_percent', (string) $data['parcel_admin_commission_percent'], 'commission');
        $this->settings->set('ride_admin_commission_percent', (string) $data['ride_admin_commission_percent'], 'commission');

        return back()->with('success', 'Commission settings saved.');
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parcel_type' => ['required', Rule::in(['normal', 'fragile', 'document'])],
            'min_weight' => ['required', 'numeric', 'min:0'],
            'max_weight' => ['required', 'numeric', 'gt:min_weight'],
            'base_charge' => ['required', 'numeric', 'min:0'],
            'per_km_charge' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
