@props(['order', 'currency' => 'BDT'])

@php($money = fn ($v) => number_format((float) $v, 2) . ' ' . $currency)

<div class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700">
    <table class="min-w-full text-sm">
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.base_fare') }}</td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->base_fare) }}</td></tr>
            @if ($order->distance_charge)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.distance_charge') }} @if($order->distance_km)<span class="text-gray-400">({{ number_format($order->distance_km, 1) }} km)</span>@endif</td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->distance_charge) }}</td></tr>
            @endif
            @if ($order->time_charge)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.time_charge') }} @if($order->duration_minutes)<span class="text-gray-400">({{ $order->duration_minutes }} min)</span>@endif</td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->time_charge) }}</td></tr>
            @endif
            @if ($order->surge_amount > 0)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.surge') }} <span class="text-gray-400">({{ number_format($order->surge_multiplier, 1) }}x)</span></td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->surge_amount) }}</td></tr>
            @endif
            @if ($order->delivery_charge)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.delivery_charge') }}</td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->delivery_charge) }}</td></tr>
            @endif
            @if ($order->tip_amount > 0)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.tip') }}</td><td class="px-4 py-2 text-end text-gray-800 dark:text-gray-100">{{ $money($order->tip_amount) }}</td></tr>
            @endif
            <tr class="bg-gray-50 dark:bg-gray-800"><td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">{{ __('admin.subtotal') }}</td><td class="px-4 py-2 text-end font-medium text-gray-800 dark:text-gray-100">{{ $money($order->subtotal) }}</td></tr>
            @if ($order->coupon_discount > 0)
                <tr><td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ __('admin.coupon_discount') }} @if($order->coupon)<span class="text-gray-400">({{ $order->coupon->code }})</span>@endif</td><td class="px-4 py-2 text-end text-red-600">−{{ $money($order->coupon_discount) }}</td></tr>
            @endif
            <tr class="bg-indigo-50"><td class="px-4 py-3 font-semibold text-indigo-700">{{ __('admin.total') }}</td><td class="px-4 py-3 text-end text-lg font-bold text-indigo-700">{{ $money($order->total_amount) }}</td></tr>
            @if (! is_null($order->admin_commission))
                <tr><td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ __('admin.admin_commission') }}</td><td class="px-4 py-2 text-end text-gray-600 dark:text-gray-400">{{ $money($order->admin_commission) }}</td></tr>
            @endif
            @if (! is_null($order->driver_earning))
                <tr><td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ __('admin.driver_earning') }}</td><td class="px-4 py-2 text-end text-gray-600 dark:text-gray-400">{{ $money($order->driver_earning) }}</td></tr>
            @endif
        </tbody>
    </table>
</div>
