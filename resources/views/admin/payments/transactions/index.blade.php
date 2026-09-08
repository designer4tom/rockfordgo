@extends('layouts.admin')

@section('title', 'Transactions')
@section('page_title', 'Transaction Overview')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($money = fn ($v) => number_format((float) $v, 0) . ' ' . $currency)

@section('content')
    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        @foreach ([
            [__('admin.total_revenue'), $stats['total_revenue'], 'text-gray-900 dark:text-gray-100'],
            [__('admin.todays_revenue'), $stats['today_revenue'], 'text-green-600 dark:text-green-400'],
            [__('admin.commission'), $stats['commission'], 'text-indigo-600 dark:text-indigo-400'],
            [__('admin.pending_withdrawals'), $stats['pending_withdrawals'], 'text-yellow-600 dark:text-yellow-400'],
            [__('admin.refunds_issued'), $stats['refunds'], 'text-red-600 dark:text-red-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold {{ $color }}">{{ $money($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <select name="type" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_types') }}</option>
                <option value="credit" @selected(($filters['type'] ?? '') === 'credit')>{{ __('admin.credit') }}</option>
                <option value="debit" @selected(($filters['type'] ?? '') === 'debit')>{{ __('admin.debit') }}</option>
            </select>
            <select name="owner_type" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_owners') }}</option>
                <option value="driver" @selected(($filters['owner_type'] ?? '') === 'driver')>{{ __('admin.driver') }}</option>
                <option value="user" @selected(($filters['owner_type'] ?? '') === 'user')>{{ __('admin.customer') }}</option>
            </select>
            <select name="category" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_categories') }}</option>
                @foreach (['trip_earning','parcel_earning','commission','cod_collection','cod_payout','withdrawal','refund','referral_bonus','top_up','due_payment'] as $cat)
                    <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ ucwords(str_replace('_',' ',$cat)) }}</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="min" value="{{ $filters['min'] ?? '' }}" placeholder="{{ __('admin.min_amount') }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="number" step="0.01" name="max" value="{{ $filters['max'] ?? '' }}" placeholder="{{ __('admin.max_amount') }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
                <a href="{{ route('admin.payments.transactions') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.reset') }}</a>
            </div>
        </div>
    </form>

    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transactions->total() }} {{ __('admin.transactions_count') }}</p>
        <a href="{{ route('admin.payments.transactions.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('admin.export_csv') }}
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($transactions->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.id') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.owner') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.category') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.balance_after') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.note') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($transactions as $tx)
                            <tr class="rr-row">
                                <td class="px-4 py-3 text-gray-400 dark:text-gray-500">#{{ $tx->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-800 dark:text-gray-100">{{ $tx->owner_name }}</span>
                                    <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs {{ $tx->owner_type === 'driver' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' }}">{{ ucfirst($tx->owner_type) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($tx->order)
                                        <a href="{{ route('admin.orders.show', $tx->order->id) }}" class="text-indigo-600 hover:text-indigo-800">{{ $tx->order->order_number }}</a>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_',' ',$tx->category)) }}</td>
                                <td class="px-4 py-3 text-end font-medium {{ $tx->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $tx->type === 'credit' ? '↑ +' : '↓ −' }}{{ number_format($tx->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($tx->balance_after, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $tx->note ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('d M, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $transactions->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_data') }}" />
        @endif
    </div>
@endsection
