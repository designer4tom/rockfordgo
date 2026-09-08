@csrf
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-4 max-w-2xl">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.title') }}</label>
        <input name="title" value="{{ old('title', $tip->title) }}" required
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
        @error('title')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.description') }}</label>
        <textarea name="description" rows="4" required
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">{{ old('description', $tip->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.icon') }} ({{ __('admin.image') }})</label>
            @if ($tip->icon)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($tip->icon) }}" class="w-14 h-14 rounded-lg object-cover border border-gray-200 dark:border-gray-700 mb-2">
            @endif
            <input type="file" name="icon" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-300 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 dark:file:text-indigo-300">
            @error('icon')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sort_order') }}</label>
            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $tip->sort_order ?? 0) }}"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
        </div>
    </div>

    <label class="inline-flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tip->is_active)) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
    </label>

    <div class="flex gap-2 pt-2">
        <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
        <a href="{{ route('admin.safety-tips.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.cancel') }}</a>
    </div>
</div>
