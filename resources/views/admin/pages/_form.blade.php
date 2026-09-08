@csrf
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-4 max-w-3xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.title') }}</label>
            <input name="title" value="{{ old('title', $page->title) }}" required
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
            @error('title')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.app_type') }}</label>
            <select name="app_type" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
                @foreach (['customer' => __('admin.customer'), 'driver' => __('admin.driver'), 'common' => __('admin.common')] as $val => $label)
                    <option value="{{ $val }}" @selected(old('app_type', $page->app_type) === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('app_type')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.slug') }}</label>
        <input name="slug" value="{{ old('slug', $page->slug) }}" required placeholder="privacy-policy"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.slug_help') }}</p>
        @error('slug')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.content') }} (HTML)</label>
        <textarea name="content" rows="16"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">{{ old('content', $page->content) }}</textarea>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.html_content_help') }}</p>
        @error('content')<p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
    </div>

    <label class="inline-flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page->is_active)) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
    </label>

    <div class="flex gap-2 pt-2">
        <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
        <a href="{{ route('admin.pages.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.cancel') }}</a>
    </div>
</div>
