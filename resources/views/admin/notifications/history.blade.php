@extends('layouts.admin')

@section('title', __('admin.broadcast_history'))
@section('page_title', __('admin.broadcast_history'))

@section('content')
    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.notifications.broadcast') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('admin.new_broadcast') }}
        </a>
    </div>

    @php($targetLabels = ['all_customers' => 'All Customers', 'all_drivers' => 'All Drivers', 'zone_customers' => 'Zone Customers', 'zone_drivers' => 'Zone Drivers'])

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($broadcasts->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.title') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.target') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.scheduled_at') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.sent_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($broadcasts as $b)
                            @php($sc = ['pending' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300', 'sent' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300', 'failed' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300'])
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $b->title }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-400 max-w-xs truncate">{{ $b->body }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $targetLabels[$b->target_type] ?? $b->target_type }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sc[$b->status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ ucfirst($b->status) }}</span></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $b->scheduled_at?->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $b->sent_at?->format('d M Y, H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($broadcasts->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $broadcasts->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_broadcasts_yet')" :createRoute="route('admin.notifications.broadcast')" :createLabel="__('admin.new_broadcast')" />
        @endif
    </div>
@endsection
