@props([
    // identifies the table in the admin's saved preferences
    'table' => 'table',
    // ['key' => 'Label', ...] in display order
    'columns' => [],
    // columns the admin has hidden; also applied server-side so there is no flash
    'hidden' => [],
])

<div x-data="{
        open: false,
        hidden: @js(array_values($hidden)),
        toggle(key) {
            this.hidden = this.hidden.includes(key)
                ? this.hidden.filter(k => k !== key)
                : [...this.hidden, key];
            this.apply();
        },
        showAll() { this.hidden = []; this.apply(); },
        saveTimer: null,
        init() {
            // A debounced save still pending when the page goes away would be
            // lost, so flush it on the way out (keepalive survives unload).
            addEventListener('pagehide', () => this.flush(true));
        },
        save(keepalive = false) {
            fetch('{{ route('admin.table-columns') }}', {
                method: 'POST',
                keepalive,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ table: @js($table), hidden: [...this.hidden] }),
            });
        },
        flush(keepalive = false) {
            if (!this.saveTimer) return;
            clearTimeout(this.saveTimer);
            this.saveTimer = null;
            this.save(keepalive);
        },
        apply() {
            document.querySelectorAll('[data-col]').forEach(el => {
                el.classList.toggle('hidden', this.hidden.includes(el.dataset.col));
            });

            // Debounced: ticking several boxes quickly would otherwise fire
            // overlapping writes that can clobber each other.
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                this.saveTimer = null;
                this.save();
            }, 350);
        },
     }"
     @keydown.escape.window="open = false"
     class="relative inline-flex">

    <button type="button" @click="open = !open" @click.outside="open = false"
        class="relative inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4v16M15 4v16"/>
        </svg>
        <span class="hidden sm:inline">{{ __('admin.columns') }}</span>
        <template x-if="hidden.length">
            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[11px] font-bold text-white"
                  x-text="{{ count($columns) }} - hidden.length"></span>
        </template>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.end
         class="absolute end-0 top-full z-30 mt-2 w-56 rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <p class="px-4 pb-1 pt-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.columns') }}</p>

        <div class="max-h-72 overflow-y-auto">
            @foreach ($columns as $key => $label)
                <label class="flex cursor-pointer items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                    <input type="checkbox" value="{{ $key }}"
                           :checked="!hidden.includes('{{ $key }}')"
                           @change="toggle('{{ $key }}')"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                    <span class="flex-1 truncate">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <button type="button" @click="showAll()" x-show="hidden.length"
            class="mt-1 w-full border-t border-gray-100 px-4 py-2 text-start text-xs font-semibold text-indigo-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-indigo-400 dark:hover:bg-gray-700">
            {{ __('admin.show_all_columns') }}
        </button>
    </div>
</div>
