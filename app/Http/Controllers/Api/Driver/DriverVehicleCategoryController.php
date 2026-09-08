<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;

class DriverVehicleCategoryController extends Controller
{
    use ApiResponse;

    /**
     * All active vehicle categories — used by the driver app during
     * registration / vehicle setup to pick which category they drive.
     */
    public function index()
    {
        $categories = VehicleCategory::with('service:id,name,type')
            ->where('is_active', true)
            ->whereHas('service', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (VehicleCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon ? Storage::url($c->icon) : null,
                'capacity' => $c->capacity,
                'service_id' => $c->service_id,
                'service_name' => $c->service?->name,
                'service_type' => $c->service?->type,
            ])
            ->values();

        return $this->success($categories, 'Vehicle categories fetched.');
    }
}
