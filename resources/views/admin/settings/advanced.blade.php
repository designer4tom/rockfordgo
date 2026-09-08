@extends('layouts.admin')

@section('title', 'Advanced Settings')
@section('page_title', 'Advanced Settings')

@section('content')
    <x-admin.settings-tabs />

    <div class="space-y-6 max-w-4xl">
        {{-- Cache --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.cache_management') }}</h3>
            <p class="text-xs text-gray-400 dark:text-gray-400 mb-4">{{ __('admin.last_cleared') }} {{ $lastCleared ?? 'never' }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach (['all' => __('admin.clear_all_cache'), 'settings' => __('admin.clear_settings_cache'), 'dashboard' => __('admin.clear_dashboard_cache')] as $scope => $label)
                    <form method="POST" action="{{ route('admin.settings.advanced.clear-cache') }}">
                        @csrf
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <button class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </section>

        {{-- Queue --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.queue_management') }}</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.queue_driver') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $queueDriver }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.failed_jobs') }}</dt><dd class="font-medium {{ $failedJobs > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-100' }}">{{ $failedJobs }}</dd></div>
            </dl>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.settings.advanced.retry-jobs') }}">@csrf<button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.retry_failed_jobs') }}</button></form>
                <form method="POST" action="{{ route('admin.settings.advanced.clear-failed-jobs') }}" onsubmit="return confirm('Clear all failed jobs?')">@csrf<button class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.clear_failed_jobs') }}</button></form>
            </div>
        </section>

        {{-- Database + version --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.database_app') }}</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.db_size') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $dbSize }} MB</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.version') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $version }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.environment') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $environment }}</dd></div>
            </dl>
            <div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                @foreach ($tableCounts as $table => $count)
                    <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">{{ $table }}</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ number_format($count) }}</span></div>
                @endforeach
            </div>
        </section>

        {{-- Log viewer --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.log_viewer') }}</h3>
                <form method="POST" action="{{ route('admin.settings.advanced.clear-log') }}" onsubmit="return confirm('Clear the log file?')">@csrf<button class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">{{ __('admin.clear_log') }}</button></form>
            </div>
            <pre class="rounded-lg bg-gray-900 text-gray-200 text-xs p-4 overflow-x-auto max-h-80 whitespace-pre-wrap">{{ $logTail }}</pre>
        </section>

        {{-- Danger zone --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border-2 border-red-200 dark:border-red-900/40 shadow-sm p-6">
            <h3 class="font-semibold text-red-700 dark:text-red-400 mb-1">{{ __('admin.danger_zone') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('admin.danger_zone_subtitle') }}</p>
            <div x-data="{ confirm: '' }">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{!! __('admin.danger_zone_instructions', ['confirm' => '<strong>' . __('admin.confirm_word') . '</strong>']) !!}</p>
                <input type="text" x-model="confirm" placeholder="{{ __('admin.type_confirm_hint') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm mb-3">
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.settings.advanced.clear-all-orders') }}" onsubmit="return confirm('FINAL WARNING: permanently delete ALL orders?')">
                        @csrf
                        <input type="hidden" name="confirm" :value="confirm">
                        <button type="submit" :disabled="confirm !== 'CONFIRM'" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">{{ __('admin.clear_all_orders') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.settings.advanced.reset-statistics') }}" onsubmit="return confirm('Reset all cached statistics?')">
                        @csrf
                        <input type="hidden" name="confirm" :value="confirm">
                        <button type="submit" :disabled="confirm !== 'CONFIRM'" class="rounded-lg border border-red-300 dark:border-red-800 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-40 disabled:cursor-not-allowed">{{ __('admin.reset_statistics') }}</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
