@extends('layouts.admin')

@section('title', 'COD Reconciliation')
@section('page_title', 'COD Reconciliation')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($money = fn ($v) => number_format((float) $v, 0) . ' ' . $currency)

@section('content')
    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        @foreach ([
            [__('admin.total_cod_collected'), $summary['collected'], 'text-gray-900 dark:text-gray-100'],
            [__('admin.total_settled'), $summary['settled'], 'text-green-600 dark:text-green-400'],
            [__('admin.pending_settlement'), $summary['pending'], 'text-yellow-600 dark:text-yellow-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $money($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <select name="driver_id" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_drivers') }}</option>
                @foreach ($drivers as $d)<option value="{{ $d->id }}" @selected(($filters['driver_id'] ?? '') == $d->id)>{{ $d->name }}</option>@endforeach
            </select>
            <select name="status" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_statuses') }}</option>
                <option value="settled" @selected(($filters['status'] ?? '') === 'settled')>{{ __('admin.settled') }}</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>{{ __('admin.pending') }}</option>
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            <a href="{{ route('admin.payments.cod-reconciliation') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.reset') }}</a>
            <a href="{{ route('admin.payments.cod-reconciliation.export', request()->query()) }}" class="ms-auto inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.export_csv') }}</a>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($orders->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.sender') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.cod_amount') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.delivery') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.sender_payout') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($orders as $o)
                            @php($payout = max(0, (float) $o->cod_amount - (float) $o->delivery_charge))
                            <tr class="rr-row">
                                <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $o->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $o->order_number }}</a></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $o->driver->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $o->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-end text-gray-800 dark:text-gray-100">{{ number_format($o->cod_amount, 2) }}</td>
                                <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($o->delivery_charge, 2) }}</td>
                                <td class="px-4 py-3 text-end font-medium text-gray-800 dark:text-gray-100">{{ number_format($payout, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if ($o->is_settled)
                                        <span class="inline-flex rounded-full bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.settled') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.pending') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ ($o->completed_at ?? $o->created_at)->format('d M, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_cod_orders') }}" />
        @endif
    </div>
@endsection
