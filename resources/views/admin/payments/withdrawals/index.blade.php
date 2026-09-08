@extends('layouts.admin')

@section('title', __('admin.withdrawals'))
@section('page_title', __('admin.withdrawal_management'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($canWrite = adminCan('payments', 'write'))
@php($methodColors = ['bank' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300', 'bkash' => 'bg-pink-50 text-pink-700 dark:bg-pink-900/20 dark:text-pink-300', 'nagad' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-300'])

@section('content')
    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
    {{-- Owner segment: Driver | Customer --}}
    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @foreach (['driver' => __('admin.driver_withdrawals'), 'customer' => __('admin.customer_withdrawals')] as $oKey => $oLabel)
            <a href="{{ route('admin.payments.withdrawals', ['owner' => $oKey, 'tab' => $tab]) }}"
               class="px-4 py-2 text-sm font-semibold {{ $owner === $oKey ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ $oLabel }}
                @php($oCount = $oKey === 'customer' ? $pendingCustomerCount : $pendingCount)
                @if ($oCount > 0)<span class="ms-1 inline-flex rounded-full {{ $owner === $oKey ? 'bg-white/25 text-white' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300' }} px-1.5 text-xs">{{ $oCount }}</span>@endif
            </a>
        @endforeach
    </div>

    @if ($canWrite)
        <a href="{{ route('admin.payments.withdrawal-methods.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">⚙ {{ __('admin.manage_withdrawal_methods') }}</a>
    @endif
    </div>

    {{-- Status tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex flex-wrap gap-1 -mb-px">
            @foreach (['pending' => __('admin.pending'), 'approved' => __('admin.approved'), 'rejected' => __('admin.rejected'), 'all' => __('admin.all')] as $key => $label)
                <a href="{{ route('admin.payments.withdrawals', ['tab' => $key, 'owner' => $owner]) }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === $key ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                    {{ $label }}
                    @if ($key === 'pending' && ($owner === 'customer' ? $pendingCustomerCount : $pendingCount) > 0)
                        <span class="ms-1 inline-flex rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 px-1.5 text-xs">{{ $owner === 'customer' ? $pendingCustomerCount : $pendingCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    <form method="POST" action="{{ route('admin.payments.withdrawals.bulk-approve') }}"
          x-data="{ selected: [], get count() { return this.selected.length } }"
          onsubmit="return confirm(@js(__('admin.approve_withdrawals_confirm')).replace(':count', count))">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            @if ($canWrite && $tab === 'pending')
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40" x-show="count > 0" x-cloak>
                    <span class="text-sm text-gray-600 dark:text-gray-400"><span x-text="count"></span> {{ __('admin.selected') }}</span>
                    <button class="rounded-lg bg-green-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.approve_all') }}</button>
                </div>
            @endif

            @if ($withdrawals->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                @if ($canWrite && $tab === 'pending')
                                    <th class="px-4 py-3"><input type="checkbox" @change="selected = $event.target.checked ? @js($withdrawals->pluck('id')) : []" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600"></th>
                                @endif
                                <th class="px-4 py-3.5 text-start">{{ $owner === 'customer' ? __('admin.customer') : __('admin.driver') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.method') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.account') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.requested') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                                @if ($canWrite)<th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($withdrawals as $w)
                                @php($acc = is_array($w->account_details) ? implode(' · ', array_map(fn($v) => is_string($v) ? $v : json_encode($v), $w->account_details)) : '')
                                @php($accMasked = strlen($acc) > 6 ? substr($acc, 0, 3) . str_repeat('*', max(0, strlen($acc) - 6)) . substr($acc, -3) : $acc)
                                <tr class="rr-row" x-data="{ rejecting: false }">
                                    @if ($canWrite && $tab === 'pending')
                                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $w->id }}" x-model="selected" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600"></td>
                                    @endif
                                    <td class="px-4 py-3">
                                        @if ($w->user)
                                            <a href="{{ route('admin.customers.show', $w->user->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $w->user->name }}</a>
                                            <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($w->user->phone) }}</p>
                                        @elseif ($w->driver)
                                            <a href="{{ route('admin.drivers.show', $w->driver->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">{{ $w->driver->name }}</a>
                                            <p class="text-xs text-gray-400 dark:text-gray-400">{{ maskPhone($w->driver->phone) }}</p>
                                        @else — @endif
                                    </td>
                                    <td class="px-4 py-3 text-end font-semibold text-gray-800 dark:text-gray-100">{{ number_format($w->amount, 2) }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $methodColors[$w->method] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ ucfirst($w->method) }}</span></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $accMasked ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $w->created_at->format('d M, H:i') }}</td>
                                    <td class="px-4 py-3">
                                        @php($sc = ['pending' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300', 'approved' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300', 'rejected' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300'])
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sc[$w->status] }}">{{ ucfirst($w->status) }}</span>
                                        @if ($w->status === 'rejected' && $w->rejection_reason)<p class="text-xs text-red-500 dark:text-red-400 mt-0.5">{{ $w->rejection_reason }}</p>@endif
                                    </td>
                                    @if ($canWrite)
                                        <td class="px-4 py-3 text-end">
                                            @if ($w->status === 'pending')
                                                <div class="inline-flex items-center gap-2" x-show="!rejecting">
                                                    <button type="button" form="approve-{{ $w->id }}" onclick="document.getElementById('approve-{{ $w->id }}').submit()" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-xs font-medium">{{ __('admin.approve') }}</button>
                                                    <button type="button" @click="rejecting = true" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.reject') }}</button>
                                                </div>
                                                <div x-show="rejecting" x-cloak class="flex items-center gap-1 justify-end">
                                                    <input type="text" form="reject-{{ $w->id }}" name="reason" required placeholder="{{ __('admin.reason') }}" class="rounded border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-2 py-1 text-xs w-28">
                                                    <button type="submit" form="reject-{{ $w->id }}" class="text-red-600 dark:text-red-400 text-xs font-medium">{{ __('admin.ok') }}</button>
                                                    <button type="button" @click="rejecting = false" class="text-gray-400 dark:text-gray-500 text-xs">✕</button>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $w->processedBy->name ?? '—' }}</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($withdrawals->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $withdrawals->links() }}</div>
                @endif
            @else
                <x-admin.empty-state :message="__('admin.no_withdrawal_requests')" />
            @endif
        </div>
    </form>

    {{-- Per-row action forms (kept outside the bulk form to avoid nesting) --}}
    @if ($canWrite)
        @foreach ($withdrawals as $w)
            @if ($w->status === 'pending')
                <form id="approve-{{ $w->id }}" method="POST" action="{{ route('admin.payments.withdrawals.approve', $w->id) }}" class="hidden">@csrf</form>
                <form id="reject-{{ $w->id }}" method="POST" action="{{ route('admin.payments.withdrawals.reject', $w->id) }}" class="hidden">@csrf</form>
            @endif
        @endforeach
    @endif
@endsection
