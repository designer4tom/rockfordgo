<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class
        ]);
        
        if(config('app.env') == 'local'){
            $this->call([
                AdminSeeder::class,
                ServiceSeeder::class,
                VehicleCategorySeeder::class,
                DemoDataSeeder::class,
                CustomerDueSeeder::class,
                MultiPayGatewaySeeder::class, // demo test gateways (NOT run by the buyer installer)
            ]);
        }
    }
}
