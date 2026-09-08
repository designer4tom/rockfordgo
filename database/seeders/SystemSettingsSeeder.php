<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // general
            'general' => [
                'app_name' => 'ReadyRide',
                'currency' => 'BDT',
                'timezone' => 'Asia/Dhaka',
                'support_email' => 'support@readyride.com',
                'support_phone' => '+8801700000000',
            ],
            // driver
            'driver' => [
                'due_limit_amount' => '500',
                'withdrawal_minimum_amount' => '100',
                'request_timeout_seconds' => '30',
            ],
            // ride
            'ride' => [
                'ride_share_enabled' => 'false',
                'surge_enabled' => 'false',
            ],
            // parcel
            'parcel' => [
                'parcel_payment_timing' => 'both', // before / after / both
                'cod_enabled' => 'true',
                'proof_of_delivery_enabled' => 'true',
                'proof_type' => 'otp', // otp / photo / signature
            ],
            // booking
            'booking' => [
                'scheduled_booking_enabled' => 'true',
                'max_schedule_days' => '7',
                'driver_assign_before_minutes' => '30',
            ],
            // cancellation
            'cancellation' => [
                'cancellation_fee_enabled' => 'false',
                'cancellation_grace_minutes' => '5',
                'cancellation_fee_amount' => '30',
            ],
            // referral
            'referral' => [
                'referral_enabled' => 'true',
                'referrer_bonus' => '50',
                'referee_bonus' => '30',
            ],
            // tip
            'tip' => [
                'tip_enabled' => 'true',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => $group]
                );
            }
        }
    }
}
