<?php

namespace Database\Seeders;

use App\Models\WithdrawalMethod;
use Illuminate\Database\Seeder;

class WithdrawalMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'bkash', 'name' => 'bKash', 'instructions' => 'Enter your bKash account number', 'sort_order' => 1],
            ['code' => 'nagad', 'name' => 'Nagad', 'instructions' => 'Enter your Nagad account number', 'sort_order' => 2],
            ['code' => 'bank', 'name' => 'Bank Transfer', 'instructions' => 'Enter your bank account details', 'sort_order' => 3],
        ];

        foreach ($methods as $m) {
            WithdrawalMethod::updateOrCreate(['code' => $m['code']], $m + ['is_active' => true]);
        }
    }
}
