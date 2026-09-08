@extends('layouts.admin')

@section('title', __('admin.safety_tips'))
@section('page_title', __('admin.safety_tips'))
@section('page_subtitle', __('admin.safety_tips_subtitle'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.safety-tips.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_safety_tip') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($tips->count())
            <table class="rr-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3.5 text-start">#</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.icon') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.title') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @foreach ($tips as $tip)
                        <tr class="rr-row">
                            <td class="px-6 py-3 text-gray-400 dark:text-gray-500">{{ $tip->sort_order }}</td>
                            <td class="px-6 py-3">
                                @if ($tip->icon)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($tip->icon) }}" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                                @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                            </td>
                            <td class="px-6 py-3">
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $tip->title }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-400 truncate max-w-md">{{ \Illuminate\Support\Str::limit($tip->description, 80) }}</p>
                            </td>
                            <td class="px-6 py-3">
                                @if (adminCan('settings', 'write'))
                                    <x-admin.toggle :route="route('admin.safety-tips.toggle-status', $tip->id)" :checked="$tip->is_active" />
                                @else
                                    <span class="text-xs {{ $tip->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $tip->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @if (adminCan('settings', 'write'))
                                        <a href="{{ route('admin.safety-tips.edit', $tip->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                    @endif
                                    @if (adminCan('settings', 'delete'))
                                        <form method="POST" action="{{ route('admin.safety-tips.destroy', $tip->id) }}" onsubmit="return confirm('{{ __('admin.confirm_delete') }}');">
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
            @if ($tips->hasPages())<div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $tips->links() }}</div>@endif
        @else
            <x-admin.empty-state :message="__('admin.no_safety_tips_yet')"
                :createRoute="adminCan('settings','write') ? route('admin.safety-tips.create') : null"
                :createLabel="__('admin.new_safety_tip')" />
        @endif
    </div>
@endsection
