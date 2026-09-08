@extends('layouts.admin')

@section('title', __('admin.business_settings'))
@section('page_title', __('admin.business_settings'))

@php($w = adminCan('settings', 'write'))
@php($input = 'block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm')
@php($label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1')

@section('content')
    <x-admin.settings-tabs />

    <form method="POST" action="{{ route('admin.settings.business.update') }}" class="space-y-6 max-w-4xl">
        @csrf

        {{-- Business Identity --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.business_identity') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">{{ __('admin.business_name') }}</label>
                    <input name="app_name" value="{{ $settings['app_name'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.currency') }}</label>
                    <input name="currency" value="{{ $settings['currency'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">{{ __('admin.currency_symbol') }}</label>
                        <input name="currency_symbol" value="{{ $settings['currency_symbol'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('admin.currency_position') }}</label>
                        <select name="currency_position" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                            <option value="before" @selected($settings['currency_position'] === 'before')>{{ __('admin.before') }}</option>
                            <option value="after" @selected($settings['currency_position'] === 'after')>{{ __('admin.after') }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.timezone') }}</label>
                    <input name="timezone" value="{{ $settings['timezone'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">{{ __('admin.date_format') }}</label>
                        <input name="date_format" value="{{ $settings['date_format'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('admin.time_format') }}</label>
                        <select name="time_format" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                            <option value="12h" @selected($settings['time_format'] === '12h')>12h</option>
                            <option value="24h" @selected($settings['time_format'] === '24h')>24h</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.country_code') }}</label>
                    <input name="country_code" value="{{ $settings['country_code'] }}" maxlength="2" {{ $w ? '' : 'disabled' }} class="{{ $input }}" placeholder="BD">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.phone_code') }}</label>
                    <input name="phone_code" value="{{ $settings['phone_code'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}" placeholder="+880">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.phone_regex') }}</label>
                    <input name="phone_regex" value="{{ $settings['phone_regex'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }} font-mono">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.phone_example') }}</label>
                    <input name="phone_example" value="{{ $settings['phone_example'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
            </div>
        </section>

        {{-- Payment Methods --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.payment_methods') }}</h3>
            <div class="space-y-3">
                @foreach ([
                    'pay_online_enabled' => __('admin.online_payment_card'),
                    'pay_wallet_enabled' => __('admin.wallet_payment'),
                    'pay_cash_enabled' => __('admin.cash_payment'),
                ] as $key => $lbl)
                    <label class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $lbl }}</span>
                        <span>
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($settings[$key]) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Commission --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.commission') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">{{ __('admin.ride_commission_percent') }}</label>
                    <input name="ride_admin_commission_percent" type="number" step="0.01" min="0" max="100" value="{{ $settings['ride_admin_commission_percent'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.parcel_commission_percent') }}</label>
                    <input name="parcel_admin_commission_percent" type="number" step="0.01" min="0" max="100" value="{{ $settings['parcel_admin_commission_percent'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
            </div>
        </section>

        {{-- Wallet & Limits --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.wallet_limits') }}</h3>
            <label class="inline-flex items-center gap-2 mb-4">
                <input type="hidden" name="wallet_topup_enabled" value="0">
                <input type="checkbox" name="wallet_topup_enabled" value="1" @checked($settings['wallet_topup_enabled']) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.allow_wallet_topup') }}</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}">{{ __('admin.minimum_topup') }}</label>
                    <input name="wallet_topup_min" type="number" step="0.01" min="0" value="{{ $settings['wallet_topup_min'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.maximum_topup') }}</label>
                    <input name="wallet_topup_max" type="number" step="0.01" min="0" value="{{ $settings['wallet_topup_max'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.min_withdrawal_amount') }}</label>
                    <input name="min_withdrawal_amount" type="number" step="0.01" min="0" value="{{ $settings['min_withdrawal_amount'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.min_recharge_amount') }}</label>
                    <input name="min_recharge_amount" type="number" step="0.01" min="0" value="{{ $settings['min_recharge_amount'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.max_recharge_amount') }}</label>
                    <input name="max_recharge_amount" type="number" step="0.01" min="0" value="{{ $settings['max_recharge_amount'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('admin.topup_quick_amounts') }}</label>
                    <input name="topup_quick_amounts" value="{{ $settings['topup_quick_amounts'] }}" {{ $w ? '' : 'disabled' }} class="{{ $input }}" placeholder="100,200,500,1000">
                    <p class="mt-1 text-xs text-gray-400">{{ __('admin.comma_separated_numbers') }}</p>
                </div>
            </div>
        </section>

        @if ($w)
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_settings') }}</button>
        @endif
    </form>
@endsection
