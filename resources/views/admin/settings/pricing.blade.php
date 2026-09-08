@extends('layouts.admin')

@section('title', 'Pricing Settings')
@section('page_title', 'Pricing Settings')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($bool = fn ($v) => in_array(strtolower((string) $v), ['true','1','yes','on'], true))
@php($w = adminCan('settings', 'write'))

@section('content')
    <x-admin.settings-tabs />

    <form method="POST" action="{{ route('admin.settings.pricing.update') }}" class="space-y-6 max-w-4xl"
          x-data="{
            rideShare: {{ $bool($settings['ride_share_enabled']) ? 'true' : 'false' }},
            codEnabled: {{ $bool($settings['cod_enabled']) ? 'true' : 'false' }},
            podEnabled: {{ $bool($settings['proof_of_delivery_enabled']) ? 'true' : 'false' }},
            schedEnabled: {{ $bool($settings['scheduled_booking_enabled']) ? 'true' : 'false' }},
            cancelFee: {{ $bool($settings['cancellation_fee_enabled']) ? 'true' : 'false' }},
            tipEnabled: {{ $bool($settings['tip_enabled']) ? 'true' : 'false' }}
          }">
        @csrf

        {{-- Ride Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.ride_settings') }}</h3>
            <label class="inline-flex items-center gap-2 mb-4">
                <input type="hidden" name="ride_share_enabled" value="0">
                <input type="checkbox" name="ride_share_enabled" value="1" x-model="rideShare" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.ride_share_enable') }}</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="rideShare" x-cloak>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_pool_passengers') }}</label>
                    <input name="max_pool_passengers" type="number" min="2" max="4" value="{{ $settings['max_pool_passengers'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.pool_discount_percent') }}</label>
                    <input name="pool_discount_percent" type="number" min="0" max="100" step="0.1" value="{{ $settings['pool_discount_percent'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>
        </section>

        {{-- Parcel Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.parcel_settings') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.payment_timing') }}</label>
                    <div class="flex gap-4">
                        @foreach (['before' => 'Before', 'after' => 'After', 'both' => 'Both'] as $val => $label)
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="parcel_payment_timing" value="{{ $val }}" @checked($settings['parcel_payment_timing'] === $val) {{ $w ? '' : 'disabled' }} class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-4 space-y-3">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="cod_enabled" value="0">
                    <input type="checkbox" name="cod_enabled" value="1" x-model="codEnabled" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.cod_enable') }}</span>
                </label>
                <br>
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="proof_of_delivery_enabled" value="0">
                    <input type="checkbox" name="proof_of_delivery_enabled" value="1" x-model="podEnabled" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.proof_of_delivery_enable') }}</span>
                </label>
                <div x-show="podEnabled" x-cloak class="max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.proof_type') }}</label>
                    <select name="proof_type" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        @foreach (['otp' => 'OTP', 'photo' => 'Photo', 'signature' => 'Signature'] as $val => $label)
                            <option value="{{ $val }}" @selected($settings['proof_type'] === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="max-w-xs mt-4" x-show="codEnabled" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sender_due_limit_amount') }}</label>
                    <input name="sender_due_limit_amount" type="number" min="0" step="0.01" value="{{ $settings['sender_due_limit_amount'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.sender_due_limit_hint') }}</p>
                </div>
            </div>
        </section>

        {{-- Booking Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.booking_settings') }}</h3>
            <label class="inline-flex items-center gap-2 mb-4">
                <input type="hidden" name="scheduled_booking_enabled" value="0">
                <input type="checkbox" name="scheduled_booking_enabled" value="1" x-model="schedEnabled" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.scheduled_booking_enable') }}</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="schedEnabled" x-cloak>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_schedule_days') }}</label>
                    <input name="max_schedule_days" type="number" min="1" max="30" value="{{ $settings['max_schedule_days'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.driver_assign_before_minutes') }}</label>
                    <input name="driver_assign_before_minutes" type="number" min="15" max="120" value="{{ $settings['driver_assign_before_minutes'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>
        </section>

        {{-- Cancellation Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.cancellation_settings') }}</h3>
            <label class="inline-flex items-center gap-2 mb-4">
                <input type="hidden" name="cancellation_fee_enabled" value="0">
                <input type="checkbox" name="cancellation_fee_enabled" value="1" x-model="cancelFee" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.cancellation_fee_enable') }}</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="cancelFee" x-cloak>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.grace_period_minutes') }}</label>
                    <input name="cancellation_grace_minutes" type="number" min="0" value="{{ $settings['cancellation_grace_minutes'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.cancellation_fee') }} ({{ $currency }})</label>
                    <input name="cancellation_fee_amount" type="number" min="0" step="0.01" value="{{ $settings['cancellation_fee_amount'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>
        </section>

        {{-- Driver Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.driver_settings') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.due_limit') }} ({{ $currency }})</label>
                    <input name="due_limit_amount" type="number" min="0" step="0.01" value="{{ $settings['due_limit_amount'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.withdrawal_minimum') }} ({{ $currency }})</label>
                    <input name="withdrawal_minimum_amount" type="number" min="0" step="0.01" value="{{ $settings['withdrawal_minimum_amount'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.customer_withdrawal_minimum') }} ({{ $currency }})</label>
                    <input name="user_withdrawal_minimum" type="number" min="0" step="0.01" value="{{ $settings['user_withdrawal_minimum'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.request_timeout_sec') }}</label>
                    <input name="request_timeout_seconds" type="number" min="10" max="300" value="{{ $settings['request_timeout_seconds'] }}" {{ $w ? '' : 'disabled' }}
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>
        </section>

        {{-- Tip Settings --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.tip_settings') }}</h3>
            <label class="inline-flex items-center gap-2 mb-4">
                <input type="hidden" name="tip_enabled" value="0">
                <input type="checkbox" name="tip_enabled" value="1" x-model="tipEnabled" {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.tip_enable') }}</span>
            </label>
            <div x-show="tipEnabled" x-cloak class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.tip_amounts_comma_separated') }}</label>
                <input name="tip_amounts" type="text" value="{{ $settings['tip_amounts'] }}" placeholder="10,20,50,100" {{ $w ? '' : 'disabled' }}
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
        </section>

        @if ($w)
            <div class="flex justify-end">
                <button class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('admin.save_settings') }}</button>
            </div>
        @endif
    </form>
@endsection
