@extends('layouts.admin')

@section('title', 'Coupons')
@section('page_title', 'Coupon Management')
@section('page_subtitle', __('admin.coupons_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <div class="flex items-center justify-between mb-5">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_code') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.search') }}</button>
        </form>
        @if (adminCan('settings', 'write'))
            <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.new_coupon') }}
            </a>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($coupons->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.coupon_code') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.discount_value') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.max_discount') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.valid') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.service_type') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.used') }}</th>
                            <th class="px-4 py-3.5 text-center">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($coupons as $coupon)
                            <tr class="rr-row" x-data="{ copied: false }">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $coupon->code }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $coupon->code }}'); copied = true; setTimeout(() => copied = false, 1200)" class="ms-1 text-xs text-gray-400 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        <span x-text="copied ? '✓' : '⧉'"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : number_format($coupon->discount_value, 2) . ' ' . $currency }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $coupon->max_discount ? number_format($coupon->max_discount, 0) : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $coupon->valid_from->format('d M y') }} – {{ $coupon->valid_until->format('d M y') }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-xs capitalize">{{ $coupon->service_type }}</span></td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                                    <a href="{{ route('admin.coupons.usages', $coupon->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}</a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if (adminCan('settings', 'write'))
                                        <form method="POST" action="{{ route('admin.coupons.toggle-status', $coupon->id) }}">
                                            @csrf
                                            <button class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $coupon->is_active ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                                {{ $coupon->is_active ? __('admin.active') : __('admin.inactive') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $coupon->is_active ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">{{ $coupon->is_active ? __('admin.active') : __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex items-center gap-3">
                                        @if (adminCan('settings', 'write'))
                                            <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.edit') }}</a>
                                        @endif
                                        @if (adminCan('settings', 'delete'))
                                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon->id) }}" onsubmit="return confirm('{{ __('admin.delete_coupon_confirm') }} {{ $coupon->code }}?')">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($coupons->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $coupons->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_coupons')" :createRoute="adminCan('settings','write') ? route('admin.coupons.create') : null" :createLabel="__('admin.new_coupon')" />
        @endif
    </div>
@endsection
