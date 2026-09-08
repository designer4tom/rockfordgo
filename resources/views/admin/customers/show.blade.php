@extends('layouts.admin')

@section('title', $customer->name)
@section('page_title', 'Customer: ' . $customer->name)

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($canPay = adminCan('payments', 'write'))
@php($canWrite = adminCan('users', 'write'))
@php($txLabels = [
    'trip_earning' => 'Trip Payment',
    'parcel_earning' => 'Parcel Payment',
    'refund' => 'Refund',
    'referral_bonus' => 'Referral Bonus',
    'top_up' => 'Wallet Top-up',
    'commission' => 'Commission',
    'cod_collection' => 'COD Collection',
    'cod_payout' => 'COD Payout',
    'withdrawal' => 'Withdrawal',
    'due_payment' => 'Due Payment',
])

@section('content')
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.back') }}
        </a>
        @if ($canWrite)
            <form method="POST" action="{{ route('admin.customers.toggle-block', $customer->id) }}"
                  x-data="{ open: false }" @submit="if (!{{ $customer->is_active ? 'true' : 'false' }}) { open = false }">
                @csrf
                @if ($customer->is_active)
                    <div x-data="{ show: false }">
                        <button type="button" @click="show = true" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.block_customer') }}</button>
                        <div x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="show = false">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.block') }} {{ $customer->name }}?</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.block_customer_warning') }}</p>
                                <textarea name="reason" rows="3" placeholder="{{ __('admin.reason_optional') }}" class="mt-4 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"></textarea>
                                <div class="mt-4 flex justify-end gap-2">
                                    <button type="button" @click="show = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.confirm_block') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <button onclick="return confirm('{{ __('admin.unblock_customer_confirm') }}')" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.unblock_customer') }}</button>
                @endif
            </form>
        @endif
    </div>

    <x-admin.tab-nav :current="$tab" :baseUrl="route('admin.customers.show', $customer->id)" :tabs="[
        'profile' => __('admin.profile'),
        'trips' => __('admin.trips_and_parcels'),
        'wallet' => __('admin.wallet'),
        'disputes' => __('admin.complaints'),
        'referrals' => __('admin.referrals'),
    ]" />

    {{-- ========== PROFILE ========== --}}
    @if ($tab === 'profile')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($customer->name,0,1)) }}</div>
                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $customer->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $customer->phone }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $customer->email ?? '—' }}</p>
                <div class="mt-3">
                    @if ($customer->is_active)
                        <span class="inline-flex rounded-full bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.active') }}</span>
                    @else
                        <span class="inline-flex rounded-full bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.blocked') }}</span>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-4 mb-6">
                    <p class="text-xs text-indigo-500 dark:text-indigo-400">{{ __('admin.wallet_balance') }}</p>
                    <p class="text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($customer->wallet_balance, 2) }} <span class="text-base font-medium">{{ $currency }}</span></p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.total_trips') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $customer->total_trips }}</p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.total_parcels') }}</p><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $customer->total_parcels }}</p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.referral_code') }}</p><p class="text-lg font-mono font-bold text-gray-900 dark:text-gray-100">{{ $customer->referral_code }}</p></div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.referred_by') }}</dt>
                        <dd class="font-medium text-gray-800 dark:text-gray-100">
                            @if ($customer->referrer)
                                <a href="{{ route('admin.customers.show', $customer->referrer->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ $customer->referrer->name }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.joined') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $customer->created_at->format('d M Y') }}</dd></div>
                </dl>
            </div>
        </div>

    {{-- ========== TRIPS & PARCELS ========== --}}
    @elseif ($tab === 'trips')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <form method="GET" class="flex flex-wrap items-center gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
                <input type="hidden" name="tab" value="trips">
                <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                    <option value="">{{ __('admin.all_types') }}</option>
                    <option value="ride" @selected(request('type') === 'ride')>{{ __('admin.ride') }}</option>
                    <option value="parcel" @selected(request('type') === 'parcel')>{{ __('admin.parcel') }}</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.filter') }}</button>
            </form>
            @if ($orders->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.route') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.driver') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.payment') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($orders as $order)
                                <tr class="rr-row">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $order->order_number }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-xs capitalize">{{ $order->type }}</span></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $order->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ \Illuminate\Support\Str::limit($order->pickup_address, 20) }} → {{ \Illuminate\Support\Str::limit($order->drop_address, 20) }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->driver->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-end font-medium text-gray-800 dark:text-gray-100">{{ number_format($order->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 capitalize">{{ $order->payment_method }}</td>
                                    <td class="px-4 py-3"><x-admin.status-badge :status="$order->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
                @endif
            @else
                <x-admin.empty-state message="{{ __('admin.no_orders_found') }}" />
            @endif
        </div>

    {{-- ========== WALLET ========== --}}
    @elseif ($tab === 'wallet')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.current_balance') }}</p>
                    <p class="mt-1 text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($customer->wallet_balance, 2) }} <span class="text-base font-medium text-gray-400 dark:text-gray-400">{{ $currency }}</span></p>
                </div>

                {{-- Sender due (COD delivery charge) --}}
                @php($duePct = $dueLimit > 0 ? min(100, round($customer->due_amount / $dueLimit * 100)) : 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.due_amount') }}</p>
                    <p class="mt-1 text-3xl font-bold {{ $customer->due_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">{{ number_format($customer->due_amount, 2) }} <span class="text-base font-medium text-gray-400 dark:text-gray-400">{{ $currency }}</span></p>
                    <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-900/40 overflow-hidden"><div class="h-full bg-{{ $duePct >= 100 ? 'red' : 'amber' }}-500" style="width: {{ $duePct }}%"></div></div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($customer->due_amount, 0) }} / {{ number_format($dueLimit, 0) }} {{ __('admin.due_limit') }}
                        @if ($customer->due_amount >= $dueLimit)<span class="text-red-600 dark:text-red-400 font-medium">· {{ __('admin.customers_at_limit') }}</span>@endif
                    </p>
                </div>

                @if ($canPay)
                    {{-- Manual adjustment --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6" x-data="{ open: false, confirmed: false }">
                        <button @click="open = !open" class="w-full rounded-lg bg-gray-800 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900 dark:hover:bg-gray-600">{{ __('admin.manual_adjustment') }}</button>
                        <form method="POST" action="{{ route('admin.customers.wallet-adjust', $customer->id) }}" x-show="open" x-cloak class="mt-4 space-y-3">
                            @csrf
                            <div class="flex gap-4 text-sm">
                                <label class="inline-flex items-center gap-1"><input type="radio" name="direction" value="add" checked class="text-indigo-600"> {{ __('admin.add') }}</label>
                                <label class="inline-flex items-center gap-1"><input type="radio" name="direction" value="deduct" class="text-indigo-600"> {{ __('admin.deduct') }}</label>
                            </div>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="{{ __('admin.amount') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                            <textarea name="reason" rows="2" required placeholder="{{ __('admin.reason') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"></textarea>
                            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"><input type="checkbox" x-model="confirmed" class="rounded text-indigo-600"> {{ __('admin.confirm_adjustment') }}</label>
                            <button :disabled="!confirmed" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">{{ __('admin.apply_adjustment') }}</button>
                        </form>
                    </div>

                    {{-- Manual refund --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.issue_refund') }}</button>
                        <form method="POST" action="{{ route('admin.customers.refund', $customer->id) }}" x-show="open" x-cloak class="mt-4 space-y-3">
                            @csrf
                            <input type="number" name="order_id" min="1" placeholder="{{ __('admin.order_id_optional') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="{{ __('admin.refund_amount') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                            <textarea name="reason" rows="2" required placeholder="{{ __('admin.reason') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"></textarea>
                            <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.issue_refund') }}</button>
                        </form>
                    </div>

                    {{-- Recharge — clears due first, then tops up balance --}}
                    <form method="POST" action="{{ route('admin.customers.recharge', $customer->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-3">
                        @csrf
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.recharge') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.recharge_help') }}</p>
                        <input name="amount" type="number" step="0.01" min="0.01" placeholder="{{ __('admin.amount') }}" required class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                        <input name="note" type="text" placeholder="{{ __('admin.note') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                        <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ __('admin.recharge') }}</button>
                    </form>

                    {{-- Clear sender due --}}
                    <form method="POST" action="{{ route('admin.customers.clear-due', $customer->id) }}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-3" onsubmit="return confirm('{{ __('admin.clear_due') }}?');">
                        @csrf
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.clear_due') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.clear_due_help') }}</p>
                        <input type="text" name="reason" required placeholder="{{ __('admin.reason') }}" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
                        <button class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50" {{ $customer->due_amount <= 0 ? 'disabled' : '' }}>{{ __('admin.clear_due') }}</button>
                    </form>
                @endif
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.transaction_history') }}</h3>
                @if ($transactions->count())
                    <div class="overflow-x-auto">
                        <table class="rr-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.category') }}</th>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.note') }}</th>
                                    <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                                    <th class="px-4 py-3.5 text-end">{{ __('admin.balance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                @foreach ($transactions as $tx)
                                    <tr class="rr-row">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-100">{{ $txLabels[$tx->category] ?? ucwords(str_replace('_', ' ', $tx->category)) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $tx->note ?? '—' }}</td>
                                        <td class="px-4 py-3 text-end font-medium {{ $tx->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $tx->type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($tx->balance_after, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($transactions->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $transactions->links() }}</div>
                    @endif
                @else
                    <x-admin.empty-state message="{{ __('admin.no_transactions_yet') }}" />
                @endif
            </div>
        </div>

        {{-- Sender due history (COD delivery charge) --}}
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <h3 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.due_history') }}</h3>
            @if ($dueTransactions->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.type') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.note') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('admin.due_after') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($dueTransactions as $due)
                                <tr class="rr-row">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $due->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $due->type === 'added' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' }}">
                                            {{ $due->type === 'added' ? __('admin.due_added') : __('admin.due_paid') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $due->order->order_number ?? $due->note ?? '—' }}</td>
                                    <td class="px-4 py-3 text-end font-medium {{ $due->type === 'added' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $due->type === 'added' ? '+' : '−' }}{{ number_format($due->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-end text-gray-600 dark:text-gray-400">{{ number_format($due->due_after, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($dueTransactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $dueTransactions->links() }}</div>
                @endif
            @else
                <x-admin.empty-state message="{{ __('admin.no_customers_with_due') }}" />
            @endif
        </div>

    {{-- ========== COMPLAINTS & DISPUTES ========== --}}
    @elseif ($tab === 'disputes')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            @if ($disputes->count())
                <div class="overflow-x-auto">
                    <table class="rr-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.category') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.description') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                                <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @foreach ($disputes as $dispute)
                                <tr class="rr-row">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $dispute->order->order_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $dispute->category)) }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $dispute->description }}</td>
                                    <td class="px-4 py-3"><x-admin.status-badge :status="$dispute->status" /></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $dispute->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-admin.empty-state message="{{ __('admin.no_complaints_from_customer') }}" />
            @endif
        </div>

    {{-- ========== REFERRALS ========== --}}
    @elseif ($tab === 'referrals')
        @php($rewarded = $referrals->where('status', 'rewarded'))
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6" x-data="{ copied: false }">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.referral_code') }}</p>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-xl font-mono font-bold text-gray-900 dark:text-gray-100">{{ $customer->referral_code }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $customer->referral_code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="text-xs rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span x-text="copied ? '{{ __('admin.copied') }}' : '{{ __('admin.copy') }}'"></span>
                    </button>
                </div>
                <dl class="mt-6 space-y-3 text-sm border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.total_referred') }}</dt><dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $referrals->count() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.bonus_earned') }}</dt><dd class="font-semibold text-green-600 dark:text-green-400">{{ number_format($rewarded->sum('referrer_bonus'), 2) }} {{ $currency }}</dd></div>
                </dl>
            </div>

            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.referred_users') }}</h3>
                @if ($referrals->count())
                    <div class="overflow-x-auto">
                        <table class="rr-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.name') }}</th>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.phone') }}</th>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.joined') }}</th>
                                    <th class="px-4 py-3.5 text-end">{{ __('admin.bonus') }}</th>
                                    <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                                @foreach ($referrals as $ref)
                                    <tr class="rr-row">
                                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                            @if ($ref->referee)
                                                <a href="{{ route('admin.customers.show', $ref->referee->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $ref->referee->name }}</a>
                                            @else — @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ maskPhone($ref->referee->phone ?? null) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $ref->referee?->created_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-end text-gray-700 dark:text-gray-100">{{ number_format($ref->referrer_bonus, 2) }}</td>
                                        <td class="px-4 py-3">
                                            @if ($ref->status === 'rewarded')
                                                <span class="inline-flex rounded-full bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.rewarded') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 px-2.5 py-0.5 text-xs font-medium">{{ __('admin.pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-admin.empty-state message="{{ __('admin.no_referrals_yet') }}" />
                @endif
            </div>
        </div>
    @endif
@endsection
