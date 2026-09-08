@props([
    // route name for exports; format is appended as ?format=
    'exportRoute' => null,
    // route name that accepts the uploaded file (POST), null hides the import half
    'importRoute' => null,
    // route name serving the sample CSV template
    'templateRoute' => null,
    // current query string, so an export respects the active filters
    'query' => [],
])

<div x-data="{ open: false, importing: false, fileName: '' }"
     @keydown.escape.window="open = false; importing = false"
     class="relative inline-flex">

    <button type="button" @click="open = !open" @click.outside="open = false"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span class="hidden sm:inline">{{ __('admin.import') }} / {{ __('admin.export') }}</span>
        <svg class="h-3.5 w-3.5 opacity-50 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.end
         class="absolute end-0 top-full z-30 mt-2 w-56 rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-800">

        @if ($importRoute)
            <p class="px-4 pb-1 pt-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.import') }}</p>

            <button type="button" @click="open = false; importing = true"
                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('admin.import_csv') }}
            </button>
            <button type="button" @click="open = false; importing = true"
                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 17v-6h6v6M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                {{ __('admin.import_excel') }}
            </button>
        @endif

        <p class="mt-1 border-t border-gray-100 px-4 pb-1 pt-2.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700 dark:text-gray-500">{{ __('admin.export') }}</p>

        @foreach ([
            ['csv',  __('admin.export_csv'),   'text-sky-500',    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['pdf',  __('admin.export_pdf'),   'text-red-500',    'M9 12h6m-6 4h3m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['xlsx', __('admin.export_excel'), 'text-green-600',  'M9 17v-6h6v6M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z'],
            ['docx', __('admin.export_word'),  'text-blue-600',   'M8 8h8M8 12h8M8 16h4m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z'],
        ] as [$fmt, $label, $tone, $icon])
            <a href="{{ route($exportRoute, array_merge($query, ['format' => $fmt])) }}"
               @if ($fmt === 'pdf') target="_blank" @endif
               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4 {{ $tone }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- import dialog --}}
    @if ($importRoute)
        <div x-show="importing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div x-show="importing" x-transition.opacity class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="importing = false"></div>

            <form method="POST" action="{{ route($importRoute) }}" enctype="multipart/form-data"
                  x-show="importing" x-transition.scale.origin.center
                  class="relative w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                @csrf

                <h3 class="text-base font-bold text-gray-900 dark:text-gray-50">{{ __('admin.import') }}</h3>
                <p class="mt-1 text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">{{ __('admin.import_help_csv_xlsx') }}</p>

                <label class="mt-5 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 px-4 py-8 text-center transition hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-gray-600 dark:hover:border-indigo-600 dark:hover:bg-indigo-900/10">
                    <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6M16 16l-4-4m0 0l-4 4m4-4v9"/></svg>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="fileName || '{{ __('admin.choose_file') }}'"></span>
                    <span class="text-[11px] text-gray-400">CSV · XLSX</span>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="hidden"
                           @change="fileName = $event.target.files[0]?.name || ''">
                </label>

                @if ($templateRoute)
                    <a href="{{ route($templateRoute) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ __('admin.download_template') }}
                    </a>
                @endif

                <div class="mt-6 flex gap-3">
                    <button type="button" @click="importing = false"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="submit"
                            class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        {{ __('admin.import') }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
