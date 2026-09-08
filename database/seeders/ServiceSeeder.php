<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Ride',
                'slug' => 'ride',
                'type' => 'ride',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Parcel',
                'slug' => 'parcel',
                'type' => 'parcel',
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }
    }
}
