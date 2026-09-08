@extends('layouts.admin')

@section('title', __('admin.coupon_usages'))
@section('page_title', 'Usages: ' . $coupon->code)

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('admin.coupons.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.coupons') }}
        </a>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.total_uses') }}: <strong class="text-gray-800 dark:text-gray-100">{{ $coupon->used_count }}</strong>{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($usages->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.discount_amount') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.used_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($usages as $usage)
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    @if ($usage->user)
                                        <a href="{{ route('admin.customers.show', $usage->user->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $usage->user->name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($usage->user->phone) }}</p>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($usage->order)
                                        <a href="{{ route('admin.orders.show', $usage->order->id) }}" class="text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $usage->order->order_number }}</a>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($usage->discount_amount, 2) }} {{ $currency }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $usage->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($usages->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $usages->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_usages') }}" />
        @endif
    </div>
@endsection
