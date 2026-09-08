@extends('layouts.admin')

@section('title', 'Driver Report')
@section('page_title', 'Driver Report')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <select name="zone_id" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_zones') }}</option>
                @foreach ($zones as $z)<option value="{{ $z->id }}" @selected(($filters['zone_id'] ?? '') == $z->id)>{{ $z->name }}</option>@endforeach
            </select>
            <select name="vehicle_category_id" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_vehicles') }}</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['vehicle_category_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <select name="status" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (['approved','suspended','blocked','pending'] as $st)<option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ __('admin.' . $st) }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            <a href="{{ route('admin.reports.drivers.export', request()->query()) }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.export_csv') }}</a>
        </div>
    </form>

    {{-- Top lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.top_10_earnings') }}</h3>
            <ol class="space-y-1.5 text-sm">
                @forelse ($topByEarnings as $i => $t)
                    <li class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">{{ $i + 1 }}. {{ $t['name'] }}</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ number_format($t['total'], 0) }}</span></li>
                @empty <li class="text-gray-400 dark:text-gray-400">{{ __('admin.no_data') }}</li> @endforelse
            </ol>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">{{ __('admin.top_10_trips') }}</h3>
            <ol class="space-y-1.5 text-sm">
                @forelse ($topByTrips as $i => $t)
                    <li class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">{{ $i + 1 }}. {{ $t->name }}</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ $t->total_trips }}</span></li>
                @empty <li class="text-gray-400 dark:text-gray-400">{{ __('admin.no_data') }}</li> @endforelse
            </ol>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-red-900/40 shadow-sm p-5">
            <h3 class="font-semibold text-red-700 dark:text-red-300 mb-3 text-sm">{{ __('admin.lowest_rated') }}</h3>
            <ol class="space-y-1.5 text-sm">
                @forelse ($lowestRated as $t)
                    <li class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">{{ $t->name }}</span><span class="font-medium text-red-600 dark:text-red-400">★ {{ number_format($t->average_rating, 1) }}</span></li>
                @empty <li class="text-gray-400 dark:text-gray-400">{{ __('admin.none') }}</li> @endforelse
            </ol>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($drivers->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.zone') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.trips') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.earned') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.commission') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.due') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.rating') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.completion') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($drivers as $d)
                            <tr class="rr-row">
                                <td class="px-4 py-3"><a href="{{ route('admin.drivers.show', $d->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $d->name }}</a></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $d->zone->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">{{ $d->total_trips }}</td>
                                <td class="px-4 py-3 text-end text-gray-800 dark:text-gray-100">{{ number_format($d->total_earned, 2) }}</td>
                                <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($d->commission_paid, 2) }}</td>
                                <td class="px-4 py-3 text-end {{ $d->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($d->due_amount, 2) }}</td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">★ {{ number_format($d->average_rating, 1) }}</td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">{{ number_format($d->completion_rate, 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($drivers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $drivers->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_drivers_found') }}" />
        @endif
    </div>
@endsection
