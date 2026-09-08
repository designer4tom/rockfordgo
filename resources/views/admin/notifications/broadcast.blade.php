@extends('layouts.admin')

@section('title', __('admin.broadcast_notification'))
@section('page_title', __('admin.broadcast_notification'))

@section('content')
    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.notifications.history') }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('admin.view_history') }} →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
         x-data="{ title: '', body: '', target: 'all_customers', when: 'now' }">
        <form method="POST" action="{{ route('admin.notifications.broadcast.send') }}" class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-4">
            @csrf
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 p-3 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.target_audience') }}</label>
                <select name="target" x-model="target" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                    <option value="all_customers">{{ __('admin.all_customers') }}</option>
                    <option value="all_drivers">{{ __('admin.all_drivers') }}</option>
                    <option value="zone_customers">{{ __('admin.specific_zone_customers') }}</option>
                    <option value="zone_drivers">{{ __('admin.specific_zone_drivers') }}</option>
                </select>
            </div>

            <div x-show="target === 'zone_customers' || target === 'zone_drivers'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.zone') }}</label>
                <select name="zone_id" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                    <option value="">{{ __('admin.select_zone') }}…</option>
                    @foreach ($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.title') }} <span class="text-gray-400 dark:text-gray-400 text-xs">({{ __('admin.max_100') }})</span></label>
                <input type="text" name="title" x-model="title" maxlength="100" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.message') }} <span class="text-gray-400 dark:text-gray-400 text-xs">({{ __('admin.max_500') }})</span></label>
                <textarea name="body" x-model="body" maxlength="500" rows="4" required class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.schedule') }}</label>
                <div class="flex gap-4 text-sm">
                    <label class="inline-flex items-center gap-1"><input type="radio" name="when" value="now" x-model="when" class="text-indigo-600"> {{ __('admin.send_now') }}</label>
                    <label class="inline-flex items-center gap-1"><input type="radio" name="when" value="later" x-model="when" class="text-indigo-600"> {{ __('admin.schedule') }}</label>
                </div>
                <input type="datetime-local" name="scheduled_at" x-show="when === 'later'" x-cloak class="mt-2 block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            </div>

            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                <span x-text="when === 'now' ? '{{ __('admin.send_broadcast') }}' : '{{ __('admin.schedule_broadcast') }}'"></span>
            </button>
        </form>

        {{-- Preview --}}
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('admin.preview') }}</p>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold shrink-0">R</div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="title || '{{ __('admin.notification_title') }}'"></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 break-words" x-text="body || '{{ __('admin.message_appear_here') }}'"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">ReadyRide · {{ __('admin.now') }}</p>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.to') }}: <span x-text="target.replace('_', ' ')"></span></p>
        </div>
    </div>
@endsection
