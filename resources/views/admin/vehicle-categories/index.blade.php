@extends('layouts.admin')

@section('title', __('admin.vehicle_categories'))
@section('page_title', __('admin.vehicle_categories'))
@section('page_subtitle', __('admin.vehicle_categories_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <form method="GET" class="max-w-xs">
            <select name="service_id" onchange="this.form.submit()"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                <option value="">{{ __('admin.all_ride_services') }}</option>
                @foreach ($rideServices as $s)
                    <option value="{{ $s->id }}" @selected($serviceId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </form>
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.vehicle-categories.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_category') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($categories->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.icon') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.service') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.base') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.per_km') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.per_min') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.min_fare') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.capacity_short') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($categories as $cat)
                            <tr class="rr-row">
                                <td class="px-5 py-3">
                                    @if ($cat->icon)
                                        <img src="{{ Storage::url($cat->icon) }}" class="w-9 h-9 rounded object-cover" alt="">
                                    @else
                                        <div class="w-9 h-9 rounded bg-gray-100 dark:bg-gray-900/40 flex items-center justify-center text-gray-400 dark:text-gray-400 text-xs">—</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $cat->name }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $cat->service->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($cat->base_fare, 0) }}</td>
                                <td class="px-5 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($cat->per_km_rate, 0) }}</td>
                                <td class="px-5 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($cat->per_minute_rate, 0) }}</td>
                                <td class="px-5 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($cat->minimum_fare, 0) }}</td>
                                <td class="px-5 py-3 text-center text-gray-700 dark:text-gray-300">{{ $cat->capacity }}</td>
                                <td class="px-5 py-3">
                                    @if (adminCan('settings', 'write'))
                                        <x-admin.toggle :route="route('admin.vehicle-categories.toggle-status', $cat->id)" :checked="$cat->is_active" />
                                    @else
                                        <span class="text-xs {{ $cat->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $cat->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.vehicle-categories.edit', $cat->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.vehicle-categories.destroy', $cat->id) }}" onsubmit="return confirm('{{ __('admin.delete_category_confirm') }}');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($categories->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $categories->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_vehicle_categories') }}"
                :createRoute="adminCan('settings','write') ? route('admin.vehicle-categories.create') : null"
                createLabel="{{ __('admin.new_category') }}" />
        @endif
    </div>
@endsection
