@extends('layouts.admin')

@section('title', __('admin.review_driver'))
@section('page_title', __('admin.review') . ': ' . $driver->name)

@section('content')
    <a href="{{ route('admin.drivers.pending') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 inline-flex items-center gap-1 mb-5">
        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back_to_pending') }}
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: info + documents --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.driver_info') }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.name') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->name }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.phone') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->phone }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.email') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->email ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.zone') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->zone->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.status') }}</dt><dd><x-admin.driver-status-badge :status="$driver->status" /></dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.applied') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $driver->created_at->format('d M Y') }}</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.documents') }}</h3>
                @if ($driver->documents->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($driver->documents as $doc)
                            <x-admin.document-card :document="$doc" :driverId="$driver->id" />
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-400">{{ __('admin.no_documents_submitted') }}</p>
                @endif
            </div>
        </div>

        {{-- Right: decision panel --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sticky top-20" x-data="{ rejecting: false }">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.approval_decision') }}</h3>

                <form method="POST" action="{{ route('admin.drivers.approve', $driver->id) }}" class="mb-3" x-show="!rejecting">
                    @csrf
                    <button class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.approve_driver') }}</button>
                </form>

                <button type="button" @click="rejecting = true" x-show="!rejecting"
                    class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.reject_driver') }}</button>

                <form method="POST" action="{{ route('admin.drivers.reject', $driver->id) }}" x-show="rejecting" x-cloak class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-100">{{ __('admin.reject_reason') }}</label>
                    <textarea name="reason" rows="4" required placeholder="{{ __('admin.reject_reason_placeholder') }}"
                        class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"></textarea>
                    <div class="flex gap-2">
                        <button class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.confirm_reject') }}</button>
                        <button type="button" @click="rejecting = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
