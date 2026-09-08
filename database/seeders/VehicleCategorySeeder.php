<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;

class VehicleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rideService = Service::where('slug', 'ride')->first();

        if (! $rideService) {
            return;
        }

        $categories = [
            [
                'name' => 'Bike',
                'base_fare' => 20,
                'per_km_rate' => 12,
                'per_minute_rate' => 1,
                'minimum_fare' => 40,
                'capacity' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'Car',
                'base_fare' => 40,
                'per_km_rate' => 18,
                'per_minute_rate' => 2,
                'minimum_fare' => 80,
                'capacity' => 4,
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'base_fare' => 80,
                'per_km_rate' => 25,
                'per_minute_rate' => 3,
                'minimum_fare' => 150,
                'capacity' => 4,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            VehicleCategory::updateOrCreate(
                ['service_id' => $rideService->id, 'name' => $category['name']],
                array_merge($category, ['service_id' => $rideService->id, 'is_active' => true])
            );
        }
    }
}
