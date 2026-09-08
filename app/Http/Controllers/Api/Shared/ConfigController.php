<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

class ConfigController extends Controller
{
    use ApiResponse;

    public function __construct(private SystemSettingService $settings)
    {
    }

    public function index()
    {
        // Cached 5 min (rule #1) — hit on every app launch.
        $data = Cache::remember('api_config', 300, function () {
            $s = fn ($k, $d = null) => $this->settings->get($k, $d);
            $b = fn ($k, $d = false) => $this->settings->getBool($k, $d);

            // CSV → int array helper for "quick amount" lists.
            $csvInts = fn ($k, $d) => array_values(array_filter(array_map('intval', explode(',', (string) $s($k, $d)))));
            $tips = $csvInts('tip_amounts', '10,20,50,100');

            return [
                'app_name' => $s('app_name', config('app.name', 'ReadyRide')),
                'support_email' => $s('support_email', ''),
                'support_phone' => $s('support_phone', ''),
                'support_whatsapp' => $s('support_whatsapp', ''),
                'support_hours' => $s('support_hours', ''),
                'office_address' => $s('office_address', ''),
                'phone_code' => $s('phone_code', '+880'),
                'country_code' => strtoupper((string) $s('country_code', 'BD')),
                'country_flag' => $this->flagEmoji($s('country_code', 'BD')),
                'phone_regex' => $s('phone_regex', '^01[3-9]\\d{8}$'),
                'phone_example' => $s('phone_example', '01712345678'),
                'currency' => $s('currency', 'BDT'),
                'currency_symbol' => $s('currency_symbol', '৳'),
                'currency_position' => $s('currency_position', 'before'),
                // Payment methods (customer choices) + the live MultiPay gateways.
                'pay_online_enabled' => $b('pay_online_enabled', true),
                'pay_wallet_enabled' => $b('pay_wallet_enabled', true),
                'pay_cash_enabled' => $b('pay_cash_enabled', true),
                // Active gateways for the online-payment picker (name/label/icon; the
                // dedicated /recharge|wallet/payment-methods endpoints give the full list).
                'payment_methods' => collect(\Abedin\MultiPay\Facades\MultiPay::activeGateways())
                    ->map(fn ($g) => ['name' => $g['name'], 'label' => $g['label'], 'icon' => $g['icon']])
                    ->values()->all(),
                'ride_share_enabled' => $b('ride_share_enabled'),
                'surge_enabled' => $b('surge_enabled'),
                'scheduled_booking_enabled' => $b('scheduled_booking_enabled', true),
                'max_schedule_days' => (int) $s('max_schedule_days', 7),
                'cod_enabled' => $b('cod_enabled', true),
                'proof_of_delivery_enabled' => $b('proof_of_delivery_enabled', true),
                'cancellation_fee_enabled' => $b('cancellation_fee_enabled'),
                'cancellation_grace_minutes' => (int) $s('cancellation_grace_minutes', 5),
                'cancellation_fee_amount' => number_format((float) $s('cancellation_fee_amount', 0), 2, '.', ''),
                'tip_enabled' => $b('tip_enabled', true),
                'tip_amounts' => $tips,
                'referral_enabled' => $b('referral_enabled'),
                'referrer_bonus' => number_format((float) $s('referral_referrer_bonus', 0), 2, '.', ''),
                'referee_bonus' => number_format((float) $s('referral_referee_bonus', 0), 2, '.', ''),
                'request_timeout_seconds' => (int) $s('request_timeout_seconds', 30),
                'search_radius_km' => (float) $s('search_radius_km', 5),
                'max_radius_km' => (float) $s('max_radius_km', 15),
                'max_dispatch_attempts' => (int) $s('max_dispatch_attempts', 10),

                // Wallet — recharge / top-up / withdrawal limits.
                'min_recharge_amount' => (string) $s('min_recharge_amount', '50'),
                'max_recharge_amount' => (string) $s('max_recharge_amount', '10000'),
                'min_withdrawal_amount' => (string) $s('min_withdrawal_amount', '100'),
                'topup_quick_amounts' => $csvInts('topup_quick_amounts', '100,200,500,1000'),

                'google_maps_key' => $s('google_maps_key', ''),
                // Pusher key/cluster come from the broadcasting config (.env) so the
                // app subscribes with the exact key the backend broadcasts on. Secret never leaves the server.
                'pusher_key' => (string) config('broadcasting.connections.pusher.key'),
                'pusher_cluster' => (string) config('broadcasting.connections.pusher.options.cluster'),

                'social_links' => [
                    'facebook' => $s('social_facebook', ''),
                    'instagram' => $s('social_instagram', ''),
                    'youtube' => $s('social_youtube', ''),
                    'website' => $s('social_website', ''),
                ],
            ];
        });

        return $this->success($data, 'Config fetched.');
    }

    // Turn a 2-letter ISO country code into its flag emoji (e.g. "BD" → 🇧🇩).
    private function flagEmoji(?string $iso): string
    {
        $iso = strtoupper(trim((string) $iso));

        if (! preg_match('/^[A-Z]{2}$/', $iso)) {
            return '';
        }

        $base = 0x1F1E6; // regional indicator "A"

        return mb_chr($base + (ord($iso[0]) - 65), 'UTF-8') . mb_chr($base + (ord($iso[1]) - 65), 'UTF-8');
    }

    // Predefined cancellation reasons (by ride/parcel + user/driver).
    public function cancellationReasons()
    {
        $userReasons = [
            'Driver is taking too long',
            'Found another ride',
            'Changed my mind',
            'Wrong pickup location',
            'Other',
        ];
        $driverReasons = [
            'Customer not reachable',
            'Customer not at pickup',
            'Vehicle issue',
            'Too far from pickup',
            'Other',
        ];

        $by = request()->query('by', 'user');

        return $this->success($by === 'driver' ? $driverReasons : $userReasons, 'Cancellation reasons fetched.');
    }
}
