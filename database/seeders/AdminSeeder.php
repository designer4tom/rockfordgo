<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AdminPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::updateOrCreate(
            ['email' => 'admin@readyride.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Grant the super admin full access on every module (kept explicit
        // even though super_admin bypasses checks in Admin::hasPermission()).
        $modules = [
            'dashboard', 'users', 'drivers', 'orders', 'payments',
            'settings', 'reports', 'sos', 'disputes',
        ];

        foreach ($modules as $module) {
            AdminPermission::updateOrCreate(
                ['admin_id' => $admin->id, 'module' => $module],
                ['can_read' => true, 'can_write' => true, 'can_delete' => true]
            );
        }
    }
}
