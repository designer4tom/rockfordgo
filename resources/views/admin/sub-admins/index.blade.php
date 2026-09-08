@extends('layouts.admin')

@section('title', __('admin.sub_admins'))
@section('page_title', __('admin.sub_admin_management'))
@section('page_subtitle', __('admin.sub_admins_subtitle'))

@section('content')
    <x-admin.settings-tabs />

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex-1 max-w-sm">
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('admin.search_by_name_or_email') }}"
                    class="w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 ps-10 pe-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-400 absolute start-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </form>

        @if (adminUser()->isSuperAdmin())
            <a href="{{ route('admin.sub-admins.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.add_sub_admin') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="rr-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.email') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.role') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.last_login') }}</th>
                        <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @forelse ($admins as $admin)
                        <tr class="rr-row">
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $admin->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $admin->email }}</td>
                            <td class="px-6 py-3">
                                @if ($admin->role === 'fleet_manager')
                                    <span class="inline-flex rounded-full bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.fleet_manager') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.sub_admin') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                @if (adminUser()->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.sub-admins.toggle-status', $admin->id) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $admin->is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $admin->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            {{ $admin->is_active ? __('admin.active') : __('admin.inactive') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $admin->is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $admin->is_active ? __('admin.active') : __('admin.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $admin->last_login_at?->diffForHumans() ?? __('admin.never') }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if (adminUser()->isSuperAdmin())
                                        <a href="{{ route('admin.sub-admins.edit', $admin->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        <form method="POST" action="{{ route('admin.sub-admins.destroy', $admin->id) }}"
                                              onsubmit="return confirm('{{ __('admin.delete_sub_admin_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                        </form>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">{{ __('admin.no_sub_admins') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($admins->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $admins->links() }}
            </div>
        @endif
    </div>
@endsection
