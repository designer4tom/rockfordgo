@extends('layouts.admin')

@section('title', 'Expiring Documents')
@section('page_title', 'Document Expiry Management')

@php
    $section = function ($docs, $title, $bg, $border) {
        return compact('docs', 'title', 'bg', 'border');
    };
@endphp

@section('content')
    @foreach ([
        ['docs' => $expired, 'title' => __('admin.expired_documents'), 'tone' => 'red', 'key' => 'expired'],
        ['docs' => $within7, 'title' => __('admin.expiring_in_7_days'), 'tone' => 'red', 'key' => 'within7'],
        ['docs' => $within30, 'title' => __('admin.expiring_in_30_days'), 'tone' => 'yellow', 'key' => 'within30'],
    ] as $block)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-{{ $block['tone'] }}-50 dark:bg-{{ $block['tone'] }}-900/20">
                <h3 class="font-semibold text-{{ $block['tone'] }}-800 dark:text-{{ $block['tone'] }}-300">{{ $block['title'] }} <span class="text-{{ $block['tone'] }}-600 dark:text-{{ $block['tone'] }}-400">({{ $block['docs']->count() }})</span></h3>
                @if (adminCan('drivers', 'write') && $block['docs']->count())
                    <form method="POST" action="{{ route('admin.drivers.expiring-documents.notify-all') }}" onsubmit="return confirm('{{ __('admin.notify_all_confirm', ['count' => $block['docs']->count()]) }}')">
                        @csrf
                        <input type="hidden" name="section" value="{{ $block['key'] }}">
                        <button class="rounded-lg bg-{{ $block['tone'] }}-600 px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">{{ __('admin.notify_all') }}</button>
                    </form>
                @endif
            </div>
            @if ($block['docs']->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.document') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.expiry_date') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.days') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('admin.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($block['docs'] as $doc)
                                @php($days = (int) round(now()->startOfDay()->diffInDays($doc->expiry_date, false)))
                                <tr class="rr-row">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.drivers.show', [$doc->driver_id, 'tab' => 'documents']) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $doc->driver->name ?? '—' }}</a>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $doc->type) }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $doc->expiry_date->format('d M Y') }}</td>
                                    <td class="px-6 py-3 {{ $days < 7 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-100' }}">
                                        {{ $days < 0 ? abs($days) . ' ' . __('admin.days_ago') : $days . ' ' . __('admin.days') }}
                                    </td>
                                    <td class="px-6 py-3 text-end">
                                        @if (adminCan('drivers', 'write'))
                                            <div class="inline-flex items-center gap-3">
                                                <form method="POST" action="{{ route('admin.drivers.notify', $doc->driver_id) }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="title" value="{{ __('admin.document_expiring') }}">
                                                    <input type="hidden" name="body" value="{{ $days < 0 ? __('admin.document_expired_body', ['document' => ucfirst(str_replace('_',' ',$doc->type))]) : __('admin.document_expiring_body', ['document' => ucfirst(str_replace('_',' ',$doc->type)), 'days' => $days]) }}">
                                                    <button class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.notify_driver') }}</button>
                                                </form>
                                                @if ($block['key'] === 'expired')
                                                    <form method="POST" action="{{ route('admin.drivers.change-status', $doc->driver_id) }}" class="inline" onsubmit="return confirm('{{ __('admin.block_expired_confirm') }}')">
                                                        @csrf
                                                        <input type="hidden" name="new_status" value="blocked">
                                                        <input type="hidden" name="reason" value="{{ __('admin.expired_document_reason', ['document' => ucfirst(str_replace('_',' ',$doc->type))]) }}">
                                                        <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.block') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-400">{{ __('admin.none') }}</p>
            @endif
        </div>
    @endforeach
@endsection
