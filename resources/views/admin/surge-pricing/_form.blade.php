@php($rule = $rule ?? null)
@php($dayPills = [6 => __('admin.sat'), 0 => __('admin.sun'), 1 => __('admin.mon'), 2 => __('admin.tue'), 3 => __('admin.wed'), 4 => __('admin.thu'), 5 => __('admin.fri')])
@php($selectedDays = old('day_of_week', $rule->day_of_week ?? []))

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8" x-data="{ mult: {{ (float) old('multiplier', $rule->multiplier ?? 1.5) }} }">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.rule_name') }} <span class="text-red-500">*</span></label>
            <input name="name" type="text" value="{{ old('name', $rule->name ?? '') }}" required placeholder="{{ __('admin.rule_name_placeholder') }}"
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.zone') }}</label>
            <select name="zone_id" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                <option value="">{{ __('admin.all_zones') }}</option>
                @foreach ($zones as $z)
                    <option value="{{ $z->id }}" @selected(old('zone_id', $rule->zone_id ?? '') == $z->id)>{{ $z->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.vehicle_category') }}</label>
            <select name="vehicle_category_id" class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                <option value="">{{ __('admin.all') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('vehicle_category_id', $rule->vehicle_category_id ?? '') == $c->id)>{{ $c->name }} ({{ $c->service->name ?? '' }})</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.days_of_week') }}</label>
                <label class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <input type="checkbox" onclick="document.querySelectorAll('.day-cb').forEach(cb => cb.checked = this.checked)"
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    {{ __('admin.all_days') }}
                </label>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($dayPills as $val => $label)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="day_of_week[]" value="{{ $val }}" class="hidden peer day-cb"
                            @checked(in_array((string)$val, array_map('strval', (array) $selectedDays), true))>
                        <span class="px-3 py-1 rounded-full border dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.no_days_help') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.start_time') }} <span class="text-red-500">*</span></label>
            <input name="start_time" type="time" value="{{ old('start_time', $rule ? \Illuminate\Support\Str::substr($rule->start_time,0,5) : '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            @error('start_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.end_time') }} <span class="text-red-500">*</span></label>
            <input name="end_time" type="time" value="{{ old('end_time', $rule ? \Illuminate\Support\Str::substr($rule->end_time,0,5) : '') }}" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            @error('end_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.multiplier') }} <span class="text-red-500">*</span></label>
            <input name="multiplier" type="number" step="0.1" min="1.1" max="5.0" x-model.number="mult" required
                class="block w-full rounded-lg border border-gray-300 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 px-3 py-2.5 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            @error('multiplier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-end">
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true))
                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
            </label>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40 p-4 text-sm text-amber-800 dark:text-amber-300">
        {{ __('admin.surge_example_text') }} <span class="font-semibold" x-text="(100 * mult).toFixed(0)"></span>
        (<span x-text="mult"></span>x)
    </div>
</div>
