@extends('layouts.admin')

@section('title', __('admin.zones'))
@section('page_title', __('admin.zone_management'))
@section('page_subtitle', __('admin.zones_subtitle'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.zones.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_zone') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($zones->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.active_services') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.online_drivers') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.orders_today') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.revenue_today') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($zones as $zone)
                            <tr class="rr-row">
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $zone->name }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($zone->services as $s)
                                            <span class="inline-flex rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 text-xs">{{ $s->name }}</span>
                                        @empty
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.none') }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center text-gray-700 dark:text-gray-300">
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $zone->online_drivers_count }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">/ {{ $zone->drivers_count }}</span>
                                </td>
                                <td class="px-6 py-3 text-center text-gray-700 dark:text-gray-300">{{ optional($stats->get($zone->id))->orders_today ?? 0 }}</td>
                                <td class="px-6 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format((float) (optional($stats->get($zone->id))->revenue_today ?? 0), 2) }}</td>
                                <td class="px-6 py-3">
                                    @if (adminCan('settings', 'write'))
                                        <x-admin.toggle :route="route('admin.zones.toggle-status', $zone->id)" :checked="$zone->is_active" />
                                    @else
                                        <span class="text-xs {{ $zone->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $zone->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.zones.show', $zone->id) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 text-xs font-medium">{{ __('admin.view_on_map') }}</a>
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.zones.edit', $zone->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.zones.destroy', $zone->id) }}" onsubmit="return confirm('{{ __('admin.delete_zone_confirm') }}');">
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
            @if ($zones->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $zones->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_zones_yet')"
                :createRoute="adminCan('settings','write') ? route('admin.zones.create') : null"
                :createLabel="__('admin.new_zone')" />
        @endif
    </div>
@endsection
