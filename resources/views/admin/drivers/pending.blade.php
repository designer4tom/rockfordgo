@extends('layouts.admin')

@section('title', __('admin.pending_drivers'))
@section('page_title', __('admin.pending'))
@section('page_subtitle', __('admin.drivers_awaiting_approval'))

@section('content')
    <div class="mb-3 flex justify-end">
        <a href="{{ route('admin.drivers.index') }}"
           class="group inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700">
            {{ __('admin.all_drivers') }}
            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($drivers->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.phone') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.zone') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.documents') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.applied') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($drivers as $driver)
                            @php($docCount = $driver->documents->count())
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-gray-500 dark:bg-gray-700/60 dark:text-gray-400">#{{ $driver->id }}</span>
                                </td>

                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.drivers.review', $driver->id) }}" class="group/name flex items-center gap-2.5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 text-xs font-bold text-white shadow-sm">{{ strtoupper(substr($driver->name, 0, 1)) }}</span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-gray-800 transition-colors group-hover/name:text-indigo-600 dark:text-gray-100 dark:group-hover/name:text-indigo-400">{{ $driver->name }}</span>
                                            <span class="block text-[11px] text-yellow-600 dark:text-yellow-400">{{ __('admin.pending') }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="px-4 py-3 font-mono text-[13px] text-gray-600 dark:text-gray-400">{{ $driver->phone }}</td>

                                <td class="px-4 py-3">
                                    @if ($driver->zone)
                                        <span class="inline-flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-400">
                                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            {{ $driver->zone->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[12px] font-bold
                                        {{ $docCount > 0
                                            ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/25 dark:text-sky-300'
                                            : 'bg-gray-100 text-gray-400 dark:bg-gray-700/60 dark:text-gray-500' }}">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        {{ $docCount }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-[13px] text-gray-500 dark:text-gray-400">{{ $driver->created_at->diffForHumans() }}</td>

                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('admin.drivers.review', $driver->id) }}"
                                       class="group/review inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                        {{ __('admin.review') }}
                                        <svg class="h-3 w-3 transition-transform group-hover/review:translate-x-0.5 rtl:rotate-180 rtl:group-hover/review:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($drivers->hasPages())
                <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">{{ $drivers->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_pending_drivers') }}" />
        @endif
    </div>
@endsection
