@extends('layouts.admin')

@section('title', __('admin.two_factor_authentication'))
@section('page_title', __('admin.two_factor_authentication'))

@section('content')    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
            @if ($admin->two_factor_enabled)
                {{-- Enabled state — offer disable --}}
                <div class="flex items-center gap-3 mb-6">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-3 py-1 text-sm font-medium">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ __('admin.enabled') }}
                    </span>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.two_factor_is_active') }}</h2>
                </div>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin.two_factor_disable_intro') }}</p>

                <form method="POST" action="{{ route('admin.two-factor.disable') }}" class="space-y-4 max-w-sm">
                    @csrf
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.current_password') }}</label>
                        <input id="current_password" name="current_password" type="password" required
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                    </div>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition">
                        {{ __('admin.disable_2fa') }}
                    </button>
                </form>
            @else
                {{-- Disabled state — show QR + verification --}}
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.setup_two_factor') }}</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin.scan_qr_instructions') }}</p>

                <div class="flex flex-col sm:flex-row gap-8 items-start">
                    <div class="shrink-0 rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800">
                        {!! $qrSvg !!}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('admin.cant_scan_enter_key') }}</p>
                        <code class="block bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm font-mono break-all mb-5 dark:text-gray-100">{{ $secret }}</code>

                        <form method="POST" action="{{ route('admin.two-factor.enable') }}" class="space-y-4 max-w-xs">
                            @csrf
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.verification_code') }}</label>
                                <input id="code" name="code" type="text" inputmode="numeric" maxlength="7" required
                                    class="block w-full text-center tracking-widest rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
                                    placeholder="000000">
                            </div>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                {{ __('admin.enable_2fa') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
