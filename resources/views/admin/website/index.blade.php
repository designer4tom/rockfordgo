@extends('layouts.admin')

@section('title', __('admin.website_cms'))
@section('page_title', __('admin.website_cms'))

@php
    $pageDef = $schema[$page];
    // helper to render the current value of a single field
    $val = fn ($section, $key) => $single[$section][$key] ?? '';
@endphp

@section('content')
<div x-data="websiteCms()">
    {{-- Page + locale switchers --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="border-b border-gray-200 dark:border-gray-700 flex-1 min-w-0">
            <nav class="flex flex-wrap gap-1 -mb-px">
                @foreach ($schema as $pKey => $pDef)
                    <a href="{{ route('admin.website.index', ['page' => $pKey, 'locale' => $locale]) }}"
                       class="px-3 py-2.5 text-sm font-medium border-b-2 transition {{ $pKey === $page ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        {{ $pDef['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
        <div class="flex items-center gap-2">
            {{-- Locale pills --}}
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                @foreach ($languages as $lang)
                    <a href="{{ route('admin.website.index', ['page' => $page, 'locale' => $lang->name]) }}"
                       class="px-3 py-1.5 text-xs font-semibold {{ $lang->name === $locale ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        {{ strtoupper($lang->name) }}
                    </a>
                @endforeach
            </div>
            <a href="{{ url($page === 'home' ? '/' : '/' . $page) }}" target="_blank" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.preview') }} →</a>
        </div>
    </div>

    @if ($locale !== ($languages->firstWhere('is_default', true)->name ?? 'en'))
        <p class="mb-4 text-xs text-amber-700 bg-amber-50 dark:bg-amber-900/20 dark:text-amber-300 rounded-lg px-3 py-2">
            {{ __('admin.translation_fallback_note') }}
        </p>
    @endif

    {{-- ===== Single sections ===== --}}
    @foreach ($pageDef['single'] ?? [] as $section => $def)
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-5 max-w-3xl">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $def['label'] }}</h3>
            <form @submit.prevent="saveSection($el)" enctype="multipart/form-data"
                  action="{{ route('admin.website.update', ['page' => $page, 'section' => $section]) }}"
                  class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <input type="hidden" name="_locale" value="{{ $locale }}">
                @foreach ($def['fields'] as $key => [$label, $type])
                    <div class="{{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                        @if ($type === 'textarea')
                            <textarea name="{{ $key }}" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">{{ $val($section, $key) }}</textarea>
                        @elseif ($type === 'image')
                            @if ($val($section, $key))
                                <img src="{{ \Illuminate\Support\Str::startsWith($val($section,$key), ['http://','https://']) ? $val($section,$key) : \Illuminate\Support\Facades\Storage::url($val($section,$key)) }}" class="h-12 rounded mb-2 border border-gray-200 dark:border-gray-700" alt="">
                            @endif
                            <input type="file" name="{{ $key }}" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-3 file:py-1.5 file:text-indigo-700 dark:file:text-indigo-300">
                        @else
                            <input name="{{ $key }}" value="{{ $val($section, $key) }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                        @endif
                    </div>
                @endforeach
                <div class="sm:col-span-2 flex items-center gap-3">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
                    <span x-show="saved === '{{ $section }}'" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('admin.saved') }} ✓</span>
                </div>
            </form>
        </section>
    @endforeach

    {{-- ===== List sections ===== --}}
    @foreach ($pageDef['list'] ?? [] as $section => $def)
        <section class="mb-6 max-w-3xl"
                 x-data="websiteList('{{ $page }}', '{{ $section }}', '{{ $locale }}', {{ \Illuminate\Support\Js::from($lists[$section] ?? []) }}, {{ \Illuminate\Support\Js::from($def['fields']) }})">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ $def['label'] }}</h3>
                <form @submit.prevent="add($el)" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @csrf
                    @foreach ($def['fields'] as $field => [$label, $type])
                        <div class="{{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                            @if ($type === 'textarea')
                                <textarea name="{{ $field }}" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm"></textarea>
                            @elseif ($type === 'image')
                                <input type="file" name="{{ $field }}" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-3 file:py-1.5 file:text-indigo-700 dark:file:text-indigo-300">
                            @else
                                <input name="{{ $field }}" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                            @endif
                        </div>
                    @endforeach
                    <div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ {{ __('admin.add') }}</button></div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <p class="px-4 pt-3 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.drag_rows_to_reorder') }}</p>
                <ul x-ref="list" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="item in items" :key="item.id">
                        <li class="p-4" :data-id="item.id">
                            <div class="flex items-start justify-between gap-3" x-show="editing !== item.id">
                                <div class="flex items-start gap-3 min-w-0">
                                    <span class="cursor-move text-gray-300 dark:text-gray-600 select-none">⠿</span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 dark:text-gray-100" x-text="item['{{ array_key_first($def['fields']) }}'] || '—'"></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="summary(item)"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs" :class="item.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'" x-text="item.is_active ? '{{ __('admin.active') }}' : '{{ __('admin.hidden') }}'"></span>
                                    <button type="button" @click="editing = item.id" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ __('admin.edit') }}</button>
                                    <button type="button" @click="remove(item)" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('admin.delete') }}</button>
                                </div>
                            </div>
                            <form x-show="editing === item.id" x-cloak @submit.prevent="update($el, item)" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @csrf
                                @foreach ($def['fields'] as $field => [$label, $type])
                                    <div class="{{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                                        @if ($type === 'textarea')
                                            <textarea name="{{ $field }}" rows="2" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm" x-text="item['{{ $field }}'] || ''"></textarea>
                                        @elseif ($type === 'image')
                                            <template x-if="item['{{ $field }}']"><img :src="imageUrl(item['{{ $field }}'])" class="h-10 rounded mb-1 border border-gray-200 dark:border-gray-700"></template>
                                            <input type="file" name="{{ $field }}" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-3 file:py-1.5 file:text-indigo-700 dark:file:text-indigo-300">
                                        @else
                                            <input name="{{ $field }}" :value="item['{{ $field }}'] || ''" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                                        @endif
                                    </div>
                                @endforeach
                                <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm dark:text-gray-300"><input type="checkbox" name="is_active" value="1" :checked="item.is_active" class="rounded text-indigo-600"> {{ __('admin.active') }}</label>
                                <div class="sm:col-span-2 flex gap-2">
                                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save') }}</button>
                                    <button type="button" @click="editing = null" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.cancel') }}</button>
                                </div>
                            </form>
                        </li>
                    </template>
                    <li x-show="items.length === 0" class="p-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_items_yet') }}</li>
                </ul>
            </div>
        </section>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const W_STORE = '{{ route('admin.website.items.store') }}';
    const W_REORDER = '{{ route('admin.website.items.reorder') }}';
    const W_ITEM = '{{ url('admin/website/items') }}';
    const W_STORAGE = '{{ Storage::url('') }}';

    function websiteCms() {
        return {
            saved: null,
            async saveSection(form) {
                const res = await fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: new FormData(form) });
                if (res.ok) { const m = form.action.split('/'); this.saved = m[m.length - 1]; setTimeout(() => this.saved = null, 1800); }
            },
        };
    }

    function websiteList(page, section, locale, initial, fields) {
        return {
            page, section, locale, items: initial, editing: null,
            fieldKeys: Object.keys(fields),
            imageUrl(v) { return (v && (v.startsWith('http://') || v.startsWith('https://'))) ? v : (W_STORAGE + v); },
            summary(item) { return this.fieldKeys.slice(1).map(k => item[k]).filter(Boolean).join(' · '); },
            init() {
                Sortable.create(this.$refs.list, { handle: '.cursor-move', animation: 150, onEnd: () => {
                    const ids = [...this.$refs.list.querySelectorAll('[data-id]')].map(el => el.dataset.id);
                    fetch(W_REORDER, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }) });
                }});
            },
            async add(form) {
                const body = new FormData(form);
                body.append('page', this.page); body.append('section', this.section); body.append('_locale', this.locale);
                const res = await fetch(W_STORE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                const data = await res.json();
                if (data.success) { this.items.push(data.item); form.reset(); }
            },
            async update(form, item) {
                const body = new FormData(form);
                body.append('_method', 'POST'); body.append('_locale', this.locale);
                const res = await fetch(`${W_ITEM}/${item.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                const data = await res.json();
                if (data.success) { const i = this.items.findIndex(x => x.id === item.id); if (i > -1) this.items[i] = data.item; this.editing = null; }
            },
            async remove(item) {
                if (!confirm('{{ __('admin.delete_item_confirm') }}')) return;
                const body = new FormData(); body.append('_method', 'DELETE');
                const res = await fetch(`${W_ITEM}/${item.id}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
                if (res.ok) this.items = this.items.filter(x => x.id !== item.id);
            },
        };
    }
</script>
@endpush
