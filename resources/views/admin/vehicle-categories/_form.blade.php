@php($category = $category ?? null)
@php($currency = \App\Models\SystemSetting::get('currency', 'BDT'))

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.service') }} <span class="text-red-500">*</span></label>
        <select name="service_id" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
            <option value="">{{ __('admin.select_ride_service') }}</option>
            @foreach ($rideServices as $s)
                <option value="{{ $s->id }}" @selected(old('service_id', $category->service_id ?? '') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('service_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }} <span class="text-red-500">*</span></label>
        <input name="name" type="text" value="{{ old('name', $category->name ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.base_fare') }} ({{ $currency }}) <span class="text-red-500">*</span></label>
        <input id="vc-base" name="base_fare" type="number" step="0.01" min="0" value="{{ old('base_fare', $category->base_fare ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('base_fare') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.per_km_rate') }} ({{ $currency }}) <span class="text-red-500">*</span></label>
        <input id="vc-km" name="per_km_rate" type="number" step="0.01" min="0" value="{{ old('per_km_rate', $category->per_km_rate ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('per_km_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.per_minute_rate') }} ({{ $currency }}) <span class="text-red-500">*</span></label>
        <input id="vc-min" name="per_minute_rate" type="number" step="0.01" min="0" value="{{ old('per_minute_rate', $category->per_minute_rate ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('per_minute_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.minimum_fare') }} ({{ $currency }}) <span class="text-red-500">*</span></label>
        <input id="vc-minfare" name="minimum_fare" type="number" step="0.01" min="0" value="{{ old('minimum_fare', $category->minimum_fare ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('minimum_fare') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.capacity') }} <span class="text-red-500">*</span></label>
        <input name="capacity" type="number" min="1" max="20" value="{{ old('capacity', $category->capacity ?? 1) }}" required
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
        @error('capacity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sort_order') }}</label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600">
    </div>

    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.icon') }}</label>
        @if ($category && $category->icon)
            <img src="{{ Storage::url($category->icon) }}" class="w-12 h-12 rounded object-cover mb-2" alt="">
        @endif
        <input name="icon" type="file" accept="image/*"
            class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100">
        @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))
                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
        </label>
    </div>
</div>

{{-- Live fare preview --}}
<div class="mt-6 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-900/40 p-5">
    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300 mb-1">{{ __('admin.fare_preview_example') }}</p>
    <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-400"><span id="fare-preview">{{ $currency }} 0</span></p>
    <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">{{ __('admin.fare_preview_formula') }}</p>
</div>

@push('scripts')
<script>
    (function () {
        const cur = @json($currency);
        const f = (id) => parseFloat(document.getElementById(id)?.value) || 0;
        const out = document.getElementById('fare-preview');
        function update() {
            const total = f('vc-base') + 5 * f('vc-km') + 15 * f('vc-min');
            const final = Math.max(total, f('vc-minfare'));
            out.textContent = cur + ' ' + final.toLocaleString(undefined, { maximumFractionDigits: 2 });
        }
        ['vc-base','vc-km','vc-min','vc-minfare'].forEach(id => document.getElementById(id)?.addEventListener('input', update));
        update();
    })();
</script>
@endpush
