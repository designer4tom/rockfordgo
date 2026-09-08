@extends('layouts.admin')

@section('title', 'Scheduled Orders')
@section('page_title', 'Scheduled Orders')
@section('page_subtitle', __('admin.scheduled_orders_subtitle'))

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
         x-data="{ cancelOpen: false, cancelId: null, cancelNum: '' }">
        @if ($orders->count())
            <div class="overflow-x-auto">
                <table class="rr-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.order_number') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.customer') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.service') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.scheduled_for') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.route') }}</th>
                            <th class="px-4 py-3.5 text-start">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.amount') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($orders as $order)
                            @php($soon = $order->scheduled_at && $order->scheduled_at->isBetween(now(), now()->addHour()))
                            <tr class="rr-row">
                                <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $order->order_number }}</a></td>
                                <td class="px-4 py-3">
                                    <p class="text-gray-800 dark:text-gray-100">{{ $order->user->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ maskPhone($order->user->phone ?? null) }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->service->name ?? ucfirst($order->type) }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $soon ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">{{ $order->scheduled_at?->format('d M Y, H:i') }}</span>
                                    @if ($soon)<span class="ms-1 text-xs text-red-500 dark:text-red-400">({{ __('admin.soon') }})</span>@endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[180px] truncate">{{ \Illuminate\Support\Str::limit($order->pickup_address, 14) }} → {{ \Illuminate\Support\Str::limit($order->drop_address, 14) }}</td>
                                <td class="px-4 py-3"><x-admin.order-status-badge :status="$order->status" /></td>
                                <td class="px-4 py-3 text-end font-medium text-gray-800 dark:text-gray-100">{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-xs font-medium">{{ __('admin.view') }}</a>
                                        @if (adminCan('orders', 'write'))
                                            <button type="button" @click="cancelId = {{ $order->id }}; cancelNum = '{{ $order->order_number }}'; cancelOpen = true" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">{{ __('admin.cancel') }}</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (adminCan('orders', 'write'))
                <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-6" @click.outside="cancelOpen = false">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.cancel_order') }} <span x-text="cancelNum"></span></h3>
                        <form method="POST" :action="`{{ url('admin/orders') }}/${cancelId}/cancel`" class="mt-4 space-y-3">
                            @csrf
                            <textarea name="reason" rows="3" required placeholder="{{ __('admin.cancellation_reason') }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="cancelOpen = false" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.close') }}</button>
                                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('admin.confirm_cancel') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $orders->links() }}</div>
            @endif
        @else
            <x-admin.empty-state message="{{ __('admin.no_upcoming_scheduled_orders') }}" />
        @endif
    </div>
@endsection
