@extends('layouts.admin')

@section('title', 'Refunds')
@section('page_title', 'Refund Management')

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($canWrite = adminCan('payments', 'write'))

@section('content')
    @if ($canWrite)
        <div class="flex justify-end mb-5" x-data="{ open: false }">
            <button @click="open = true" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.issue_refund') }}
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="open = false">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.issue_refund') }}</h3>
                    <form method="POST" action="{{ route('admin.payments.refunds.store') }}" class="mt-4 space-y-3"
                          x-data="{ confirmed: false }">
                        @csrf
                        <div x-data="{ q: '' }">
                            <input list="refund-customers" placeholder="{{ __('admin.search_customer') }}" required
                                   class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"
                                   x-on:input="$refs.uid.value = ($el.value.match(/#(\d+)/)||[])[1] || ''">
                            <datalist id="refund-customers">
                                @foreach ($recentCustomers as $c)
                                    <option value="{{ $c->name }} ({{ $c->phone }}) #{{ $c->id }}"></option>
                                @endforeach
                            </datalist>
                            <input type="hidden" name="user_id" x-ref="uid" required>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.pick_from_list') }}</p>
                        </div>
                        <input type="number" name="order_id" min="1" placeholder="{{ __('admin.order_id_optional') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="{{ __('admin.amount') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                        <textarea name="reason" rows="2" required placeholder="{{ __('admin.reason') }}" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm"></textarea>
                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"><input type="checkbox" x-model="confirmed" class="rounded text-indigo-600"> {{ __('admin.confirm_refund') }}</label>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                            <button :disabled="!confirmed" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ __('admin.issue_refund') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($refunds->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.refund_amount') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.refund_reason_or_note') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($refunds as $tx)
                            <tr class="rr-row">
                                <td class="px-4 py-3">
                                    @if ($tx->order)
                                        <a href="{{ route('admin.orders.show', $tx->order->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ $tx->order->order_number }}</a>
                                    @else <span class="text-gray-300 dark:text-gray-600">—</span> @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($tx->customer)
                                        <a href="{{ route('admin.customers.show', $tx->customer->id) }}" class="text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $tx->customer->name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ maskPhone($tx->customer->phone) }}</p>
                                    @else <span class="text-gray-400 dark:text-gray-500">#{{ $tx->owner_id }}</span> @endif
                                </td>
                                <td class="px-4 py-3 text-end font-medium text-green-600 dark:text-green-400">+{{ number_format($tx->amount, 2) }} {{ $currency }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $tx->note ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($refunds->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $refunds->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_data') }}" />
        @endif
    </div>
@endsection
