@php($coupon = $coupon ?? null)

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 p-4 text-sm text-red-700 dark:text-red-300">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-5" x-data="{ type: '{{ old('discount_type', $coupon->discount_type ?? 'percentage') }}' }">
    {{-- Code --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.coupon_code') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <div class="flex gap-2" x-data="{
            gen() { const c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; let s = ''; for (let i=0;i<8;i++) s += c[Math.floor(Math.random()*c.length)]; $refs.code.value = s; }
        }">
            <input x-ref="code" type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required
                class="flex-1 rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm uppercase font-mono outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            <button type="button" @click="gen()" class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.generate') }}</button>
        </div>
    </div>

    {{-- Description --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.description') }}</label>
        <input type="text" name="description" value="{{ old('description', $coupon->description ?? '') }}"
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
    </div>

    {{-- Discount type + value --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.discount_type') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <div class="flex gap-4 text-sm pt-2">
                <label class="inline-flex items-center gap-1"><input type="radio" name="discount_type" value="percentage" x-model="type" class="text-indigo-600"> {{ __('admin.percentage') }}</label>
                <label class="inline-flex items-center gap-1"><input type="radio" name="discount_type" value="fixed" x-model="type" class="text-indigo-600"> {{ __('admin.fixed') }}</label>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.discount_value') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $coupon->discount_value ?? '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div x-show="type === 'percentage'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_discount') }}</label>
            <input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount ?? '') }}"
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
    </div>

    {{-- Limits --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.min_order') }}</label>
            <input type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? '') }}"
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.usage_limit') }} <span class="text-gray-400 text-xs">{{ __('admin.blank_unlimited') }}</span></label>
            <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}"
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.per_user_limit') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input type="number" min="1" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit ?? 1) }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
    </div>

    {{-- Validity + service + status --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.valid_from') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input type="date" name="valid_from" value="{{ old('valid_from', isset($coupon) ? $coupon->valid_from->format('Y-m-d') : '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.valid_to') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input type="date" name="valid_until" value="{{ old('valid_until', isset($coupon) ? $coupon->valid_until->format('Y-m-d') : '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.service_type') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <select name="service_type" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                @foreach (['all' => __('admin.all'), 'ride' => __('admin.ride'), 'parcel' => __('admin.parcel')] as $val => $label)
                    <option value="{{ $val }}" @selected(old('service_type', $coupon->service_type ?? 'all') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.status') }}</label>
            <label class="inline-flex items-center gap-2 text-sm pt-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="rounded text-indigo-600"> {{ __('admin.active') }}
            </label>
        </div>
    </div>

    <div class="flex gap-2 pt-2">
        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $submitLabel ?? __('admin.save') }}</button>
        <a href="{{ route('admin.coupons.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.cancel') }}</a>
    </div>
</div>
