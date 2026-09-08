@extends('layouts.admin')

@section('title', __('admin.referrals'))
@section('page_title', __('admin.referral_overview'))
@section('page_subtitle', __('admin.referrals_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ([
            [__('admin.total_referrals'), number_format($stats['total']), 'text-gray-900 dark:text-gray-100'],
            [__('admin.rewarded'), number_format($stats['rewarded']), 'text-green-600 dark:text-green-400'],
            [__('admin.pending'), number_format($stats['pending']), 'text-yellow-600 dark:text-yellow-400'],
            [__('admin.total_bonus_paid'), number_format($stats['bonus_paid'], 0) . ' ' . $currency, 'text-indigo-600 dark:text-indigo-400'],
        ] as [$label, $value, $color])
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                <option value="">{{ __('admin.all_statuses') }}</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>{{ __('admin.pending') }}</option>
                <option value="rewarded" @selected(($filters['status'] ?? '') === 'rewarded')>{{ __('admin.rewarded') }}</option>
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            <a href="{{ route('admin.referrals.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.reset') }}</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($referrals->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.referrer') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.referee') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.referrer_bonus') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.referee_bonus') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($referrals as $ref)
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    @if ($ref->referrer)
                                        <a href="{{ route('admin.customers.show', $ref->referrer->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $ref->referrer->name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($ref->referrer->phone) }}</p>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($ref->referee)
                                        <a href="{{ route('admin.customers.show', $ref->referee->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $ref->referee->name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($ref->referee->phone) }}</p>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($ref->referrer_bonus, 2) }}</td>
                                <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-300">{{ number_format($ref->referee_bonus, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if ($ref->status === 'rewarded')
                                        <span class="inline-flex rounded-full bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.rewarded') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.pending') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $ref->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($referrals->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $referrals->links() }}</div>
            @endif
        @else
            <x-admin.empty-state :message="__('admin.no_referrals_found')" />
        @endif
    </div>
@endsection
