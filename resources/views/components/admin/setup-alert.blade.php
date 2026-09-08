{{-- The "Setup required" banner used to live here. Pending integrations are
     now surfaced by the Setup Guide chip in the navbar instead, so the banner
     no longer takes space at the top of every page. --}}

@if (config('readyride.test_mode'))
    <div class="mb-6 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 p-4 flex items-start gap-3">
        <span class="text-xl leading-none">⚠️</span>
        <div>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('admin.test_mode_on') }}</p>
            <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('admin.test_mode_banner') }}</p>
        </div>
    </div>
@endif
