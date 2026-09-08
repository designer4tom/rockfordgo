@php($pricing = $pricing ?? null)
@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <input name="name" type="text" value="{{ old('name', $pricing->name ?? '') }}" required placeholder="e.g. Normal Small 0-1kg"
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.parcel_type') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <select name="parcel_type" required
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @foreach (['normal' => __('admin.normal'), 'fragile' => __('admin.fragile'), 'document' => __('admin.document')] as $val => $label)
                <option value="{{ $val }}" @selected(old('parcel_type', $pricing->parcel_type ?? 'normal') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('parcel_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div></div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.min_weight_kg') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <input name="min_weight" type="number" step="0.01" min="0" value="{{ old('min_weight', $pricing->min_weight ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('min_weight') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.max_weight_kg') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <input name="max_weight" type="number" step="0.01" min="0" value="{{ old('max_weight', $pricing->max_weight ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('max_weight') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.base_charge') }} ({{ $currency }}) <span class="text-red-500 dark:text-red-400">*</span></label>
        <input id="pp-base" name="base_charge" type="number" step="0.01" min="0" value="{{ old('base_charge', $pricing->base_charge ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('base_charge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.per_km_charge') }} ({{ $currency }}) <span class="text-red-500 dark:text-red-400">*</span></label>
        <input id="pp-km" name="per_km_charge" type="number" step="0.01" min="0" value="{{ old('per_km_charge', $pricing->per_km_charge ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('per_km_charge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $pricing->is_active ?? true))
                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
        </label>
    </div>
</div>

<div class="mt-6 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/40 p-5">
    <p class="text-sm font-medium text-emerald-900 dark:text-emerald-300 mb-1">{{ __('admin.charge_preview') }}</p>
    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400"><span id="pp-preview">{{ $currency }} 0</span></p>
    <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">{!! __('admin.charge_preview_formula') !!}</p>
</div>

@push('scripts')
<script>
    (function () {
        const cur = @json($currency);
        const f = (id) => parseFloat(document.getElementById(id)?.value) || 0;
        const out = document.getElementById('pp-preview');
        function update() {
            const total = f('pp-base') + 5 * f('pp-km');
            out.textContent = cur + ' ' + total.toLocaleString(undefined, { maximumFractionDigits: 2 });
        }
        ['pp-base','pp-km'].forEach(id => document.getElementById(id)?.addEventListener('input', update));
        update();
    })();
</script>
@endpush
