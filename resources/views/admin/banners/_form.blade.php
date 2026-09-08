@php($banner = $banner ?? null)
@php($currentAction = old('action_type', $banner->action_type ?? 'none'))

<div x-data="{
        actionType: '{{ $currentAction }}',
        imagePreview: '{{ $banner && $banner->image ? Storage::url($banner->image) : '' }}',
        previewImage(e) {
            const file = e.target.files[0];
            if (file) { this.imagePreview = URL.createObjectURL(file); }
        }
     }"
     class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Title --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.title') }}</label>
        <input name="title" type="text" value="{{ old('title', $banner->title ?? '') }}"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Subtitle --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.subtitle') }}</label>
        <input name="subtitle" type="text" value="{{ old('subtitle', $banner->subtitle ?? '') }}"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('subtitle') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Image --}}
    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.banner_image') }} @if (!$banner) <span class="text-red-500 dark:text-red-400">*</span> @endif</label>
        <template x-if="imagePreview">
            <img :src="imagePreview" class="w-full max-w-md aspect-[2/1] rounded-lg object-cover mb-2 border border-gray-200 dark:border-gray-700" alt="">
        </template>
        <input name="image" type="file" accept="image/*" @change="previewImage($event)" {{ $banner ? '' : 'required' }}
            class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-4 file:py-2 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/30">
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ __('admin.banner_image_hint') }}</p>
        @error('image') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Button text --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.button_text') }}</label>
        <input name="button_text" type="text" value="{{ old('button_text', $banner->button_text ?? '') }}" placeholder="{{ __('admin.learn_more') }}"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('button_text') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Sort order --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sort_order') }}</label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        @error('sort_order') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Action type --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.action_type') }}</label>
        <select name="action_type" x-model="actionType"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            @foreach (['none', 'url', 'service', 'screen'] as $type)
                <option value="{{ $type }}" @selected($currentAction === $type)>{{ __('admin.action_' . $type) }}</option>
            @endforeach
        </select>
        @error('action_type') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Action value (conditional). Only the visible input submits; others are disabled. --}}
    <div>
        {{-- URL --}}
        <div x-show="actionType === 'url'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.action_url_label') }}</label>
            <input type="url" name="action_value" :disabled="actionType !== 'url'" value="{{ $currentAction === 'url' ? old('action_value', $banner->action_value ?? '') : '' }}" placeholder="https://example.com/offers"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        </div>
        {{-- Service --}}
        <div x-show="actionType === 'service'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.action_service_label') }}</label>
            <select name="action_value" :disabled="actionType !== 'service'"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                <option value="">{{ __('admin.select') }}…</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($currentAction === 'service' && (string) old('action_value', $banner->action_value ?? '') === (string) $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- Screen --}}
        <div x-show="actionType === 'screen'" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.action_screen_label') }}</label>
            <input type="text" name="action_value" :disabled="actionType !== 'screen'" value="{{ $currentAction === 'screen' ? old('action_value', $banner->action_value ?? '') : '' }}" placeholder="e.g. wallet, referral"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
        </div>
        {{-- None --}}
        <div x-show="actionType === 'none'" x-cloak>
            <p class="text-xs text-gray-400 dark:text-gray-400 pt-7">{{ __('admin.action_none_hint') }}</p>
        </div>
        @error('action_value') <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
    </div>

    {{-- Status --}}
    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active ?? true))
                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.active') }}</span>
        </label>
    </div>
</div>
