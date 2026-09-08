@extends('layouts.admin')

@section('title', __('admin.sos_alerts'))
@section('page_title', __('admin.sos_alert_management'))
@section('page_subtitle', __('admin.sos_subtitle'))

@section('content')
    <x-admin.sos-alert-banner :active="$active" />

    {{-- Status filter --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (['active' => __('admin.active'), 'acknowledged' => __('admin.acknowledged'), 'resolved' => __('admin.resolved')] as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            <a href="{{ route('admin.sos.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.reset') }}</a>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($alerts->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.triggered_by') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.location') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.triggered') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.handled_by') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($alerts as $a)
                            @php($sc = ['active' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300', 'acknowledged' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300', 'resolved' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'])
                            <tr class="rr-row">
                                <td class="px-4 py-3 text-gray-400 dark:text-gray-500">#{{ $a->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-800 dark:text-gray-100">{{ $a->triggerer_name }}</span>
                                    <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs {{ $a->triggered_by === 'driver' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' }}">{{ $a->triggered_by === 'user' ? __('admin.customer') : __('admin.driver') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($a->order)<a href="{{ route('admin.orders.show', $a->order->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ $a->order->order_number }}</a>@else — @endif
                                </td>
                                <td class="px-4 py-3" x-data="{ copied: false }">
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $a->lat }}, {{ $a->lng }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $a->lat }},{{ $a->lng }}'); copied = true; setTimeout(() => copied = false, 1200)" class="ms-1 text-xs text-gray-400 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"><span x-text="copied ? '✓' : '⧉'"></span></button>
                                </td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sc[$a->status] }}">{{ __('admin.' . $a->status) }}</span></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $a->created_at->format('d M, H:i') }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $a->acknowledgedBy->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-end"><a href="{{ route('admin.sos.show', $a->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.view') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($alerts->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $alerts->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_sos_alerts') }}" />
        @endif
    </div>
@endsection

@push('scripts')
@if ($pusherKey)
    {{-- Real-time updates via Pusher: a new SOS reloads the page + plays an alert sound. --}}
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <script>
        const pusher = new Pusher('{{ $pusherKey }}', { cluster: '{{ $pusherCluster }}' });
        const channel = pusher.subscribe('admin-sos');
        channel.bind('SosAlert', () => {
            try { new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg').play(); } catch (e) {}
            window.location.reload();
        });
    </script>
@else
    <script>
        // No Pusher configured — fall back to polling while alerts are active.
        @if ($active->count())
        setTimeout(() => window.location.reload(), 30000);
        @endif
    </script>
@endif
@endpush
