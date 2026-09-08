<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;

/**
 * Business Settings — one hub for all business-related data that used to be
 * scattered across General, Pricing/Commission and Payment:
 *   - Identity   : business name, currency, country/phone, timezone, date/time
 *   - Contact    : support channels, office address, social links
 *   - Commission : admin commission % for ride & parcel
 *   - Wallet     : top-up toggle + wallet/recharge/withdrawal limits
 *
 * All values are the SAME system_settings keys (same groups) the old pages used,
 * so /config and every other reader keep working — only the editing UI moved here.
 */
class BusinessSettingsController extends Controller implements HasMiddleware
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

    // key => system_settings group (for writes).
    private const KEYS = [
        // Identity
        'app_name' => 'general',
        'currency' => 'general',
        'currency_symbol' => 'general',
        'currency_position' => 'general',
        'timezone' => 'general',
        'date_format' => 'general',
        'time_format' => 'general',
        'country_code' => 'general',
        'phone_code' => 'general',
        'phone_regex' => 'general',
        'phone_example' => 'general',
        // Commission
        'ride_admin_commission_percent' => 'commission',
        'parcel_admin_commission_percent' => 'commission',
        // Wallet & limits
        'wallet_topup_min' => 'payment',
        'wallet_topup_max' => 'payment',
        'min_recharge_amount' => 'payment',
        'max_recharge_amount' => 'payment',
        'min_withdrawal_amount' => 'payment',
        'topup_quick_amounts' => 'payment',
    ];

    public function index()
    {
        $s = fn ($k, $d = null) => $this->settings->get($k, $d);

        return view('admin.settings.business', [
            'settings' => [
                // Identity
                'app_name' => $s('app_name', config('app.name')),
                'currency' => $s('currency', 'BDT'),
                'currency_symbol' => $s('currency_symbol', '৳'),
                'currency_position' => $s('currency_position', 'before'),
                'timezone' => $s('timezone', 'Asia/Dhaka'),
                'date_format' => $s('date_format', 'd/m/Y'),
                'time_format' => $s('time_format', '12h'),
                'country_code' => $s('country_code', 'BD'),
                'phone_code' => $s('phone_code', '+880'),
                'phone_regex' => $s('phone_regex', '^01[3-9]\\d{8}$'),
                'phone_example' => $s('phone_example', '01712345678'),
                // Commission
                'ride_admin_commission_percent' => $s('ride_admin_commission_percent', '15'),
                'parcel_admin_commission_percent' => $s('parcel_admin_commission_percent', '20'),
                // Payment methods
                'pay_online_enabled' => $this->settings->getBool('pay_online_enabled', true),
                'pay_wallet_enabled' => $this->settings->getBool('pay_wallet_enabled', true),
                'pay_cash_enabled' => $this->settings->getBool('pay_cash_enabled', true),
                // Wallet & limits
                'wallet_topup_enabled' => $this->settings->getBool('wallet_topup_enabled', true),
                'wallet_topup_min' => $s('wallet_topup_min', '50'),
                'wallet_topup_max' => $s('wallet_topup_max', '10000'),
                'min_recharge_amount' => $s('min_recharge_amount', '50'),
                'max_recharge_amount' => $s('max_recharge_amount', '10000'),
                'min_withdrawal_amount' => $s('min_withdrawal_amount', '100'),
                'topup_quick_amounts' => $s('topup_quick_amounts', '100,200,500,1000'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'currency_position' => ['required', 'in:before,after'],
            'timezone' => ['required', 'string', 'max:64'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'in:12h,24h'],
            'country_code' => ['nullable', 'string', 'regex:/^[A-Za-z]{2}$/'],
            'phone_code' => ['nullable', 'string', 'max:8'],
            'phone_regex' => ['nullable', 'string', 'max:255', function ($attr, $value, $fail) {
                if ($value !== null && $value !== '' && @preg_match('/' . $value . '/', '') === false) {
                    $fail('The phone regex is not a valid pattern.');
                }
            }],
            'phone_example' => ['nullable', 'string', 'max:30'],
            'ride_admin_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'parcel_admin_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'wallet_topup_min' => ['nullable', 'numeric', 'min:0'],
            'wallet_topup_max' => ['nullable', 'numeric', 'min:0'],
            'min_recharge_amount' => ['nullable', 'numeric', 'min:0'],
            'max_recharge_amount' => ['nullable', 'numeric', 'min:0'],
            'min_withdrawal_amount' => ['nullable', 'numeric', 'min:0'],
            'topup_quick_amounts' => ['nullable', 'string', 'max:100', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
        ]);

        // Normalise ISO country code to uppercase (e.g. "bd" → "BD").
        if ($request->filled('country_code')) {
            $request->merge(['country_code' => strtoupper($request->input('country_code'))]);
        }

        foreach (self::KEYS as $key => $group) {
            if ($request->has($key)) {
                $this->settings->set($key, (string) $request->input($key), $group);
            }
        }

        foreach (['pay_online_enabled', 'pay_wallet_enabled', 'pay_cash_enabled', 'wallet_topup_enabled'] as $toggle) {
            $this->settings->set($toggle, $request->boolean($toggle) ? 'true' : 'false', 'payment');
        }

        // Refresh the public /config cache so the apps see changes immediately.
        Cache::forget('api_config');

        return back()->with('success', 'Business settings saved.');
    }
}
