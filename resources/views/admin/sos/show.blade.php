@extends('layouts.admin')

@section('title', 'SOS #' . $alert->id)
@section('page_title', 'SOS Alert #' . $alert->id)

@php($canWrite = adminCan('sos', 'write'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.sos.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.all_alerts') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Map --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.location') }}</h3>
                @if ($mapsKey)
                    <iframe class="w-full rounded-lg border border-gray-100 dark:border-gray-700" height="360" style="border:0" loading="lazy" allowfullscreen
                        src="https://www.google.com/maps/embed/v1/place?key={{ $mapsKey }}&q={{ $alert->lat }},{{ $alert->lng }}"></iframe>
                @else
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-200 dark:border-gray-700 p-6 text-center text-sm text-gray-400 dark:text-gray-400">
                        {{ __('admin.maps_key_not_configured_short') }}
                    </div>
                @endif
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $alert->lat }}, {{ $alert->lng }}</p>
            </div>

            {{-- Triggered by + order --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.details') }}</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.triggered_by') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $triggerer['name'] ?? '—' }} <span class="text-gray-400 dark:text-gray-500">({{ $triggerer['phone'] ?? '' }})</span></dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.type') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $alert->triggered_by === 'user' ? __('admin.customer') : __('admin.driver') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.triggered_at') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $alert->created_at->format('d M Y, H:i') }}</dd></div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.order') }}</dt>
                        <dd class="font-medium text-gray-800 dark:text-gray-100">@if ($alert->order)<a href="{{ route('admin.orders.show', $alert->order->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ $alert->order->order_number }}</a>@else — @endif</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Status + actions --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                @php($sc = ['active' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300', 'acknowledged' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300', 'resolved' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'])
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.status') }}</h3>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sc[$alert->status] }}">{{ __('admin.' . $alert->status) }}</span>
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <li>• {{ __('admin.triggered') }} {{ $alert->created_at->format('d M, H:i') }}</li>
                    @if ($alert->acknowledged_at)<li>• {{ __('admin.acknowledged') }} {{ $alert->acknowledged_at->format('d M, H:i') }} {{ __('admin.by') }} {{ $alert->acknowledgedBy->name ?? 'admin' }}</li>@endif
                    @if ($alert->resolved_at)<li>• {{ __('admin.resolved') }} {{ $alert->resolved_at->format('d M, H:i') }}</li>@endif
                </ul>
                @if ($alert->note)<p class="text-sm text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-3">{{ __('admin.note') }}: {{ $alert->note }}</p>@endif

                @if ($canWrite && $alert->status !== 'resolved')
                    <div class="mt-4 space-y-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                        @if ($alert->status === 'active')
                            <form method="POST" action="{{ route('admin.sos.acknowledge', $alert->id) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="note" placeholder="{{ __('admin.note_optional') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                                <button class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">{{ __('admin.acknowledge') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.sos.resolve', $alert->id) }}" class="space-y-2">
                            @csrf
                            <textarea name="note" rows="2" required placeholder="{{ __('admin.resolution_note_required') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
                            <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.resolve') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
