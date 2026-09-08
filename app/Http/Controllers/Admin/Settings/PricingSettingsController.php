<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PricingSettingsController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['update']),
        ];
    }

    // Map each setting key to the group it belongs to.
    private const SETTING_GROUPS = [
        // Ride
        'ride_share_enabled' => 'ride',
        'max_pool_passengers' => 'ride',
        'pool_discount_percent' => 'ride',
        // Parcel
        'parcel_payment_timing' => 'parcel',
        'cod_enabled' => 'parcel',
        'proof_of_delivery_enabled' => 'parcel',
        'proof_type' => 'parcel',
        'sender_due_limit_amount' => 'parcel',
        // Booking
        'scheduled_booking_enabled' => 'booking',
        'max_schedule_days' => 'booking',
        'driver_assign_before_minutes' => 'booking',
        // Cancellation
        'cancellation_fee_enabled' => 'cancellation',
        'cancellation_grace_minutes' => 'cancellation',
        'cancellation_fee_amount' => 'cancellation',
        // Driver
        'due_limit_amount' => 'driver',
        'withdrawal_minimum_amount' => 'driver',
        'user_withdrawal_minimum' => 'driver',
        'request_timeout_seconds' => 'driver',
        // Tip
        'tip_enabled' => 'tip',
        'tip_amounts' => 'tip',
    ];

    public function index()
    {
        $s = fn ($key, $default = null) => $this->settings->get($key, $default);

        return view('admin.settings.pricing', [
            'settings' => [
                'ride_share_enabled' => $s('ride_share_enabled', 'false'),
                'max_pool_passengers' => $s('max_pool_passengers', '3'),
                'pool_discount_percent' => $s('pool_discount_percent', '0'),
                'parcel_payment_timing' => $s('parcel_payment_timing', 'both'),
                'cod_enabled' => $s('cod_enabled', 'true'),
                'proof_of_delivery_enabled' => $s('proof_of_delivery_enabled', 'true'),
                'proof_type' => $s('proof_type', 'otp'),
                'sender_due_limit_amount' => $s('sender_due_limit_amount', '500'),
                'scheduled_booking_enabled' => $s('scheduled_booking_enabled', 'true'),
                'max_schedule_days' => $s('max_schedule_days', '7'),
                'driver_assign_before_minutes' => $s('driver_assign_before_minutes', '30'),
                'cancellation_fee_enabled' => $s('cancellation_fee_enabled', 'false'),
                'cancellation_grace_minutes' => $s('cancellation_grace_minutes', '5'),
                'cancellation_fee_amount' => $s('cancellation_fee_amount', '30'),
                'due_limit_amount' => $s('due_limit_amount', '500'),
                'withdrawal_minimum_amount' => $s('withdrawal_minimum_amount', '100'),
                'user_withdrawal_minimum' => $s('user_withdrawal_minimum', '100'),
                'request_timeout_seconds' => $s('request_timeout_seconds', '30'),
                'tip_enabled' => $s('tip_enabled', 'true'),
                'tip_amounts' => $s('tip_amounts', '10,20,50,100'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'max_pool_passengers' => ['nullable', 'integer', 'min:2', 'max:4'],
            'pool_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'parcel_payment_timing' => ['required', 'in:before,after,both'],
            'proof_type' => ['nullable', 'in:otp,photo,signature'],
            'sender_due_limit_amount' => ['nullable', 'numeric', 'min:0'],
            'max_schedule_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'driver_assign_before_minutes' => ['nullable', 'integer', 'min:15', 'max:120'],
            'cancellation_grace_minutes' => ['nullable', 'integer', 'min:0'],
            'cancellation_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'due_limit_amount' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'user_withdrawal_minimum' => ['nullable', 'numeric', 'min:0'],
            'request_timeout_seconds' => ['nullable', 'integer', 'min:10', 'max:300'],
            'tip_amounts' => ['nullable', 'string'],
        ]);

        // Checkbox toggles (present => true).
        $toggles = [
            'ride_share_enabled', 'cod_enabled', 'proof_of_delivery_enabled',
            'scheduled_booking_enabled', 'cancellation_fee_enabled', 'tip_enabled',
        ];

        foreach ($toggles as $key) {
            $this->settings->set($key, $request->boolean($key) ? 'true' : 'false', self::SETTING_GROUPS[$key]);
        }

        // Plain value settings.
        foreach (self::SETTING_GROUPS as $key => $group) {
            if (in_array($key, $toggles, true)) {
                continue;
            }
            if ($request->has($key)) {
                $this->settings->set($key, (string) $request->input($key), $group);
            }
        }

        return back()->with('success', 'Pricing settings saved.');
    }
}
