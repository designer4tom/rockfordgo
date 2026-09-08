@extends('layouts.admin')

@section('title', 'Dispute #' . $dispute->id)
@section('page_title', 'Dispute #' . $dispute->id)

@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))
@php($order = $dispute->order)
@php($canWrite = adminCan('disputes', 'write'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.disputes.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> {{ __('admin.all_disputes') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Section 1: Dispute Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.dispute_info') }}</h3>
                    <x-admin.status-badge :status="$dispute->status" />
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm mt-4">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.category') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ ucwords(str_replace('_',' ',$dispute->category)) }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.submitted') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $dispute->created_at->format('d M Y, H:i') }}</dd></div>
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.raised_by') }}</dt>
                        <dd class="font-medium text-gray-800 dark:text-gray-100">
                            @if ($raiser)
                                {{ $raiser['name'] }} <span class="text-gray-400 dark:text-gray-400">({{ $raiser['phone'] }})</span>
                                <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs {{ $raiser['type'] === 'driver' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' }}">{{ $raiser['type'] === 'user' ? __('admin.customer') : __('admin.driver') }}</span>
                            @else — @endif
                        </dd>
                    </div>
                    <div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.description') }}</dt><dd class="text-gray-700 dark:text-gray-300">{{ $dispute->description }}</dd></div>
                </dl>
            </div>

            {{-- Section 2 & 3: Related Order + Stage Analysis --}}
            @if ($order)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.related_order') }}</h3>
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ $order->order_number }} →</a>
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.order_status') }}</dt><dd><x-admin.order-status-badge :status="$order->status" /></dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.payment') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100 capitalize">{{ $order->payment_method }} · {{ $order->payment_status }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.customer') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->user->name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.driver') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ $order->driver->name ?? '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.route') }}</dt><dd class="text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($order->pickup_address, 30) }} → {{ \Illuminate\Support\Str::limit($order->drop_address, 30) }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.total') }}</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ number_format($order->total_amount, 2) }} {{ $currency }}</dd></div>
                    </dl>

                    {{-- Stage analysis --}}
                    <div class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40 p-4 text-sm text-amber-800 dark:text-amber-300">
                        {{ __('admin.order_currently_in') }} <strong>{{ ucwords(str_replace('_',' ',$order->status)) }}</strong>.
                        {{ __('admin.last_activity') }}: {{ $order->updated_at->format('d M Y, H:i') }}.
                        @if ($order->driver && $order->driver->current_lat)
                            {{ $order->driver->name }} — {{ __('admin.last_location') }}: {{ $order->driver->current_lat }}, {{ $order->driver->current_lng }}
                            @if ($order->driver->last_location_at) {{ __('admin.at') }} {{ $order->driver->last_location_at->format('H:i') }} @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Section 4: Admin Action --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">{{ __('admin.admin_action') }}</h3>
                @if ($canWrite)
                    <form method="POST" action="{{ route('admin.disputes.update', $dispute->id) }}" class="space-y-4" x-data="{ refund: false }">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.internal_note') }}</label>
                            <textarea name="admin_note" rows="3" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">{{ $dispute->admin_note }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.status') }}</label>
                            <select name="status" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm">
                                @foreach (['open' => __('admin.open'), 'under_review' => __('admin.under_review'), 'resolved' => __('admin.resolved')] as $val => $label)
                                    <option value="{{ $val }}" @selected($dispute->status === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (! $dispute->refund_issued)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="issue_refund" value="1" x-model="refund" class="rounded text-indigo-600"> {{ __('admin.issue_refund_to_wallet') }}
                                </label>
                                <input type="number" step="0.01" min="0" name="refund_amount" value="{{ $order->total_amount ?? '' }}" x-show="refund" x-cloak
                                    class="mt-2 block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm" placeholder="{{ __('admin.refund_amount') }}">
                            </div>
                        @else
                            <p class="text-sm text-green-600 dark:text-green-400">{{ __('admin.refund_of') }} {{ number_format($dispute->refund_amount, 2) }} {{ $currency }} {{ __('admin.already_issued') }}</p>
                        @endif
                        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.update_dispute') }}</button>
                    </form>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-400">{{ __('admin.read_only_access') }}</p>
                @endif
            </div>

            {{-- Section 5: Action history (derived) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ __('admin.history') }}</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>• {{ __('admin.submitted') }} {{ $dispute->created_at->format('d M Y, H:i') }}</li>
                    @if ($dispute->refund_issued)<li>• {{ __('admin.refund_issued') }}: {{ number_format($dispute->refund_amount, 2) }} {{ $currency }}</li>@endif
                    @if ($dispute->resolved_at)<li>• {{ __('admin.resolved') }} {{ $dispute->resolved_at->format('d M Y, H:i') }} {{ __('admin.by') }} {{ $dispute->resolvedBy->name ?? 'admin' }}</li>@endif
                </ul>
            </div>
        </div>
    </div>
@endsection
