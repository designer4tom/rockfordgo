@php($service = $service ?? null)

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.name') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <input id="svc-name" name="name" type="text" value="{{ old('name', $service->name ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.slug') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <input id="svc-slug" name="slug" type="text" value="{{ old('slug', $service->slug ?? '') }}" required
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.slug_help') }}</p>
        @error('slug') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin.type') }} <span class="text-red-500 dark:text-red-400">*</span></label>
        <div class="flex gap-4">
            @foreach (['ride' => __('admin.ride'), 'parcel' => __('admin.parcel')] as $val => $label)
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="type" value="{{ $val }}"
                        @checked(old('type', $service->type ?? 'ride') === $val)
                        @disabled($service !== null)
                        class="text-indigo-600 dark:border-gray-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @if ($service)
            {{-- Type is locked on edit; keep value submitted --}}
            <input type="hidden" name="type" value="{{ $service->type }}">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.type_locked_help') }}</p>
        @endif
        @error('type') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sort_order') }}</label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $service->sort_order ?? 0) }}"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
    </div>

    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.icon') }}</label>
        @if ($service && $service->icon)
            <img src="{{ Storage::url($service->icon) }}" class="w-12 h-12 rounded object-cover mb-2" alt="">
        @endif
        <input name="icon" type="file" accept="image/*"
            class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-4 file:py-2 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/30">
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.icon_upload_help') }}</p>
        @error('icon') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.description') }}</label>
        <textarea name="description" rows="3"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">{{ old('description', $service->description ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true))
                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
        </label>
    </div>
</div>

@push('scripts')
<script>
    // Auto-generate slug from name (only when creating).
    (function () {
        const name = document.getElementById('svc-name');
        const slug = document.getElementById('svc-slug');
        const isEdit = {{ $service ? 'true' : 'false' }};
        if (!name || !slug || isEdit) return;
        name.addEventListener('input', () => {
            slug.value = name.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        });
    })();
</script>
@endpush
