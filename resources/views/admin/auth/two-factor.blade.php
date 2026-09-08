@extends('layouts.auth')

@section('title', __('admin.two_factor_verification'))

@section('content')
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.two_factor_verification') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.enter_6_digit_code') }}</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">
        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.two-factor.post') }}" class="space-y-5">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.authentication_code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                    required autofocus maxlength="7"
                    class="block w-full text-center tracking-[0.5em] text-lg rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 text-gray-900 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition"
                    placeholder="000000">
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                {{ __('admin.verify_and_sign_in') }}
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('admin.login') }}" class="text-sm text-gray-400 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">{{ __('admin.cancel_return_to_login') }}</a>
        </div>
    </div>
@endsection
