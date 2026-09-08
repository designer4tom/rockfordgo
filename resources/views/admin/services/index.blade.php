@extends('layouts.admin')

@section('title', __('admin.services'))
@section('page_title', 'Service Management')
@section('page_subtitle', __('admin.services_subtitle'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">Ride &amp; Parcel services offered on the platform.</p>
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.services.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_service') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($services->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.service_icon') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.sort_order') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($services as $service)
                            <tr class="rr-row">
                                <td class="px-6 py-3">
                                    @if ($service->icon)
                                        <img src="{{ Storage::url($service->icon) }}" alt="" class="w-9 h-9 rounded object-cover">
                                    @else
                                        <div class="w-9 h-9 rounded bg-gray-100 dark:bg-gray-900/40 flex items-center justify-center text-gray-400 dark:text-gray-400 text-xs">—</div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $service->name }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $service->type === 'ride' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' }}">
                                        {{ ucfirst($service->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $service->sort_order }}</td>
                                <td class="px-6 py-3">
                                    @if (adminCan('settings', 'write'))
                                        <x-admin.toggle :route="route('admin.services.toggle-status', $service->id)" :checked="$service->is_active" />
                                    @else
                                        <span class="text-xs {{ $service->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $service->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.services.edit', $service->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.services.destroy', $service->id) }}" onsubmit="return confirm('{{ __('admin.delete') }}?');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($services->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $services->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_data')"
                :createRoute="adminCan('settings','write') ? route('admin.services.create') : null"
                :createLabel="__('admin.new_service')" />
        @endif
    </div>
@endsection
