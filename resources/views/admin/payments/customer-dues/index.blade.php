@extends('layouts.admin')

@section('title', 'Customer Dues')
@section('page_title', 'Customer Due Management')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($money = fn ($v) => number_format((float) $v, 0) . ' ' . $currency)
@php($canPay = adminCan('payments', 'write'))

@section('content')
    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.total_due_amount') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $money($summary['total_due']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers_with_due') }}</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($summary['with_due']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-100 dark:border-red-900/40 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.customers_at_limit') }}</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($summary['at_limit']) }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.due_limit') }} <strong class="text-gray-700 dark:text-gray-100">{{ $money($dueLimit) }}</strong></p>
        <div class="flex gap-2 text-sm">
            <a href="{{ route('admin.payments.customer-dues') }}" class="rounded-lg px-3 py-1.5 {{ ! $showAll ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400' }}">{{ __('admin.due_gt_0') }}</a>
            <a href="{{ route('admin.payments.customer-dues', ['filter' => 'all']) }}" class="rounded-lg px-3 py-1.5 {{ $showAll ? 'bg-indigo-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400' }}">{{ __('admin.all') }}</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($customers->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.current_due') }}</th>
                            <th class="px-4 py-3 text-start font-medium w-48">{{ __('admin.limit_usage') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.last_due_added') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($customers as $customer)
                            @php($pct = $dueLimit > 0 ? min(100, round($customer->due_amount / $dueLimit * 100)) : 0)
                            <tr class="rr-row" x-data="{ clearing: false }">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $customer->name }}</a>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ maskPhone($customer->phone) }}</p>
                                </td>
                                <td class="px-4 py-3 text-end font-semibold {{ $customer->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($customer->due_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="w-full bg-gray-100 dark:bg-gray-900/40 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $pct }}%</p>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $customer->last_due_at ? \Illuminate\Support\Carbon::parse($customer->last_due_at)->format('d M Y') : '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.customers.show', ['id' => $customer->id, 'tab' => 'wallet']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.history') }}</a>
                                        @if ($canPay && $customer->due_amount > 0)
                                            <button type="button" @click="clearing = !clearing" class="text-gray-700 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white text-xs font-medium">{{ __('admin.clear_due') }}</button>
                                        @endif
                                    </div>
                                    @if ($canPay && $customer->due_amount > 0)
                                        <form method="POST" action="{{ route('admin.customers.clear-due', $customer->id) }}" x-show="clearing" x-cloak class="mt-2 flex items-center gap-1 justify-end">
                                            @csrf
                                            <input type="text" name="reason" required placeholder="{{ __('admin.reason') }}" class="rounded border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-2 py-1 text-xs w-32">
                                            <button class="rounded bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-700">{{ __('admin.clear') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $customers->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_customers_with_due') }}" />
        @endif
    </div>
@endsection
