@extends('layouts.admin')

@section('title', __('admin.disputes'))
@section('page_title', __('admin.dispute_management'))
@section('page_subtitle', __('admin.disputes_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex flex-wrap gap-1 -mb-px">
            @foreach (['open' => __('admin.open'), 'under_review' => __('admin.under_review'), 'resolved' => __('admin.resolved'), 'all' => __('admin.all')] as $key => $label)
                <a href="{{ route('admin.disputes.index', ['tab' => $key]) }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    {{ $label }}
                    @if ($key === 'open' && $counts['open'] > 0)
                        <span class="ms-1 inline-flex rounded-full bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-1.5 text-xs">{{ $counts['open'] }}</span>
                    @elseif ($key === 'under_review' && $counts['under_review'] > 0)
                        <span class="ms-1 inline-flex rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 px-1.5 text-xs">{{ $counts['under_review'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="flex flex-wrap items-center gap-3">
            <select name="category" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_categories') }}</option>
                @foreach (['driver_behavior','route_issue','overcharging','parcel_issue','payment_issue','other'] as $cat)
                    <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ ucwords(str_replace('_',' ',$cat)) }}</option>
                @endforeach
            </select>
            <select name="raised_by" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.raised_by_any') }}</option>
                <option value="user" @selected(($filters['raised_by'] ?? '') === 'user')>{{ __('admin.customer') }}</option>
                <option value="driver" @selected(($filters['raised_by'] ?? '') === 'driver')>{{ __('admin.driver') }}</option>
            </select>
            <select name="has_refund" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.refund_any') }}</option>
                <option value="yes" @selected(($filters['has_refund'] ?? '') === 'yes')>{{ __('admin.with_refund') }}</option>
                <option value="no" @selected(($filters['has_refund'] ?? '') === 'no')>{{ __('admin.no_refund') }}</option>
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($disputes->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.raised_by') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.category') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.description') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.refund') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.submitted') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($disputes as $d)
                            <tr class="rr-row">
                                <td class="px-4 py-3 text-gray-400 dark:text-gray-400">#{{ $d->id }}</td>
                                <td class="px-4 py-3">
                                    @if ($d->order)
                                        <a href="{{ route('admin.orders.show', $d->order->id) }}" class="text-indigo-600 hover:text-indigo-800">{{ $d->order->order_number }}</a>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-800 dark:text-gray-100">{{ $d->raiser_name }}</span>
                                    <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs {{ $d->raised_by === 'driver' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' }}">{{ $d->raised_by === 'driver' ? __('admin.driver') : __('admin.customer') }}</span>
                                </td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-xs">{{ ucwords(str_replace('_',' ',$d->category)) }}</span></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $d->description }}</td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$d->status" /></td>
                                <td class="px-4 py-3 text-end">{{ $d->refund_issued ? number_format($d->refund_amount, 0) . ' ' . $currency : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $d->created_at->format('d M, H:i') }}</td>
                                <td class="px-4 py-3 text-end"><a href="{{ route('admin.disputes.show', $d->id) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.view') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($disputes->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $disputes->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_disputes_found')" />
        @endif
    </div>
@endsection
