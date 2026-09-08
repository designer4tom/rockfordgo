@extends('layouts.admin')

@section('title', __('admin.login_history'))
@section('page_title', __('admin.login_history'))
@section('page_subtitle', __('admin.login_history_subtitle'))

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.recent_signin_activity') }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="rr-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.ip_address') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.browser_device') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3.5 text-start">{{ __('admin.date_time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @forelse ($logs as $log)
                        <tr class="rr-row">
                            <td class="px-6 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($log->user_agent, 60) ?: '—' }}</td>
                            <td class="px-6 py-3">
                                @if ($log->status === 'success')
                                    <span class="inline-flex rounded-full bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.success') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.failed') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 dark:text-gray-400">{{ __('admin.no_login_history_yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
