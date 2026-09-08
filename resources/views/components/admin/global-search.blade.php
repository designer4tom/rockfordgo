{{--
    Top-bar omnisearch. One input searches records (drivers, customers, orders,
    zones, coupons) plus the admin's own screens, so it doubles as a jump list.
    Results come from admin.search, which gates every group by the same
    permission that guards the matching page.
--}}
<div x-data="rrSearch()" class="gs relative shrink-0" @keydown.escape.window="close()">

    {{-- Mobile: icon only. Tapping it swaps the header row for the field. --}}
    <button type="button" @click="openMobile()"
            class="gs-trigger md:hidden rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            :aria-expanded="isOpen" aria-label="{{ __('admin.search') }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
        </svg>
    </button>

    <div class="gs-field" :class="mobile && 'gs-field-mobile'">
        <div class="gs-box" :class="isOpen && 'gs-box-open'">
            <svg class="gs-box-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
            </svg>

            <input x-ref="input" type="search" autocomplete="off" spellcheck="false"
                   x-model="q"
                   @input="onInput()"
                   @focus="isOpen = true"
                   @keydown.arrow-down.prevent="move(1)"
                   @keydown.arrow-up.prevent="move(-1)"
                   @keydown.enter.prevent="go(flat[cursor])"
                   placeholder="{{ __('admin.search_everything') }}"
                   class="gs-input">

            {{-- ⌘K hint doubles as a clear button once there is a query --}}
            <button type="button" x-show="q.length" x-cloak @click="reset(); $refs.input.focus()"
                    class="gs-clear" aria-label="{{ __('admin.clear') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <kbd class="gs-kbd" x-show="!q.length" x-cloak><span x-text="metaKey"></span>K</kbd>
        </div>

        {{-- Panel --}}
        {{-- opening animation is pure CSS: Alpine's class transitions need a
             requestAnimationFrame tick, which a throttled/background tab never
             delivers, leaving the panel stuck at display:none --}}
        <div x-show="isOpen" x-cloak @click.outside="close()" class="gs-panel">

            {{-- Loading --}}
            <template x-if="loading">
                <div class="gs-state">
                    <svg class="gs-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".2"/>
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                    {{ __('admin.searching') }}
                </div>
            </template>

            {{-- Results --}}
            <template x-if="!loading && groups.length">
                <div class="gs-scroll" x-ref="scroll">
                    <template x-for="(group, gi) in groups" :key="group.label">
                        <div class="gs-group">
                            <p class="gs-group-label" x-text="group.label"></p>
                            <template x-for="(item, ii) in group.items" :key="item.url">
                                <a :href="item.url" @click="remember(item)"
                                   @mouseenter="cursor = index(gi, ii)"
                                   class="gs-item" :class="cursor === index(gi, ii) && 'gs-item-on'"
                                   :data-i="index(gi, ii)">
                                    <span class="gs-ico" :class="'gs-ico-' + group.icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" :d="iconPath(group.icon)"/>
                                        </svg>
                                    </span>
                                    <span class="gs-text">
                                        <span class="gs-title" x-html="highlight(item.title)"></span>
                                        <span class="gs-meta" x-text="item.meta"></span>
                                    </span>
                                    <svg class="gs-go" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Empty --}}
            <template x-if="!loading && !groups.length && q.trim().length >= 2">
                <div class="gs-state gs-empty">
                    <svg class="h-8 w-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                    </svg>
                    <p class="mt-2 text-sm font-semibold">{{ __('admin.no_results_found') }}</p>
                    <p class="mt-0.5 text-xs opacity-70">“<span x-text="q"></span>”</p>
                </div>
            </template>

            {{-- Idle: recent picks, else a hint about what is searchable --}}
            <template x-if="!loading && q.trim().length < 2">
                <div>
                    <template x-if="recent.length">
                        <div class="gs-group">
                            <p class="gs-group-label">
                                {{ __('admin.recent') }}
                                <button type="button" @click="clearRecent()" class="gs-group-action">{{ __('admin.clear') }}</button>
                            </p>
                            <template x-for="item in recent" :key="item.url">
                                <a :href="item.url" class="gs-item">
                                    <span class="gs-ico gs-ico-recent">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    <span class="gs-text">
                                        <span class="gs-title" x-text="item.title"></span>
                                        <span class="gs-meta" x-text="item.meta"></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <div class="gs-hint">
                        <p class="gs-hint-lead">{{ __('admin.search_hint_lead') }}</p>
                        <div class="gs-chips">
                            @foreach ([__('admin.drivers'), __('admin.customers'), __('admin.orders'), __('admin.zones'), __('admin.coupons'), __('admin.pages')] as $chip)
                                <span class="gs-chip">{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </template>

            <div class="gs-foot">
                <span><kbd>↑</kbd><kbd>↓</kbd> {{ __('admin.to_navigate') }}</span>
                <span><kbd>↵</kbd> {{ __('admin.to_open') }}</span>
                <span><kbd>esc</kbd> {{ __('admin.to_close') }}</span>
            </div>
        </div>
    </div>

    {{-- Mobile backdrop, so the expanded field reads as a layer --}}
    <div x-show="mobile && isOpen" x-cloak @click="close()" class="gs-backdrop md:hidden"></div>
</div>

@once
<style>
    .gs-field { position: relative; }
    /* Desktop: a calm pill. Width is fixed on purpose — growing it on focus
       squeezed the page title next to it and made the header jitter. The panel
       below is absolutely positioned, so it can still be wider than the field. */
    .gs-box {
        display: none;
        align-items: center;
        gap: 8px;
        width: 220px;
        height: 40px;
        padding: 0 10px 0 12px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }
    @media (min-width: 768px) { .gs-box { display: flex; } }
    @media (min-width: 1280px) { .gs-box { width: 300px; } }
    .gs-box:hover { border-color: #c7d2fe; }
    .gs-box-open {
        background: #fff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79,70,229,.13);
    }
    html.dark .gs-box { border-color: #374151; background: #111827; }
    html.dark .gs-box:hover { border-color: #4b5563; }
    html.dark .gs-box-open { background: #0b1220; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.22); }

    .gs-box-ico { width: 17px; height: 17px; flex: none; color: #94a3b8; }
    .gs-box-open .gs-box-ico { color: #4f46e5; }
    html.dark .gs-box-open .gs-box-ico { color: #818cf8; }

    .gs-input {
        flex: 1 1 auto;
        min-width: 0;
        border: 0;
        background: transparent;
        font-size: 13.5px;
        color: #0f172a;
        outline: none;
    }
    .gs-input::placeholder { color: #94a3b8; }
    html.dark .gs-input { color: #e5e7eb; }
    html.dark .gs-input::placeholder { color: #6b7280; }
    /* the browser's own clear affordance duplicates ours */
    .gs-input::-webkit-search-cancel-button { display: none; }

    .gs-kbd {
        flex: none;
        padding: 2px 6px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1.4;
        color: #94a3b8;
        font-family: inherit;
    }
    html.dark .gs-kbd { border-color: #374151; background: #1f2937; color: #6b7280; }

    .gs-clear {
        flex: none;
        display: inline-flex;
        padding: 4px;
        border-radius: 999px;
        color: #94a3b8;
        transition: background-color .15s ease, color .15s ease;
    }
    .gs-clear:hover { background: #e2e8f0; color: #475569; }
    html.dark .gs-clear:hover { background: #374151; color: #e5e7eb; }

    /* ---- panel ---- */
    .gs-panel {
        position: absolute;
        z-index: 40;
        top: calc(100% + 8px);
        inset-inline-start: 0;
        width: 420px;
        max-width: calc(100vw - 32px);
        border-radius: 16px;
        border: 1px solid #eef2f7;
        background: #fff;
        box-shadow: 0 4px 10px rgba(15,23,42,.04), 0 24px 48px -18px rgba(15,23,42,.28);
        overflow: hidden;
    }
    @media (min-width: 1280px) { .gs-panel { width: 480px; } }
    html.dark .gs-panel { border-color: #374151; background: #111827; box-shadow: 0 24px 48px -18px rgba(0,0,0,.7); }

    /* Slide only — no opacity ramp. If the animation clock is frozen (hidden or
       throttled tab) the panel is still fully readable, just 6px high. */
    .gs-panel { animation: gs-drop .18s cubic-bezier(.22,.68,.34,1); }
    @keyframes gs-drop {
        from { transform: translateY(-6px); }
        to { transform: none; }
    }

    .gs-scroll { max-height: min(58vh, 420px); overflow-y: auto; padding: 6px; }
    .gs-scroll::-webkit-scrollbar { width: 6px; }
    .gs-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    html.dark .gs-scroll::-webkit-scrollbar-thumb { background: #4b5563; }

    .gs-group + .gs-group { border-top: 1px solid #f1f5f9; margin-top: 4px; padding-top: 4px; }
    html.dark .gs-group + .gs-group { border-top-color: #1f2937; }
    .gs-group-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px 4px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .gs-group-action { font-size: 10px; font-weight: 700; color: #6366f1; text-transform: none; letter-spacing: 0; }
    .gs-group-action:hover { text-decoration: underline; }

    .gs-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        transition: background-color .14s ease;
    }
    .gs-item-on { background: rgba(79,70,229,.08); }
    html.dark .gs-item-on { background: rgba(99,102,241,.16); }

    .gs-ico {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: #f1f5f9;
        color: #64748b;
    }
    .gs-ico svg { width: 16px; height: 16px; }
    html.dark .gs-ico { background: #1f2937; color: #9ca3af; }

    /* one tinted chip per record type — the palette stays scannable */
    .gs-ico-nav      { background: #eef2ff; color: #4f46e5; }
    .gs-ico-driver   { background: #ecfdf5; color: #059669; }
    .gs-ico-customer { background: #eff6ff; color: #2563eb; }
    .gs-ico-order    { background: #fff7ed; color: #ea580c; }
    .gs-ico-zone     { background: #f5f3ff; color: #7c3aed; }
    .gs-ico-coupon   { background: #fdf2f8; color: #db2777; }
    .gs-ico-recent   { background: #f8fafc; color: #94a3b8; }
    html.dark .gs-ico-nav      { background: rgba(99,102,241,.18); color: #a5b4fc; }
    html.dark .gs-ico-driver   { background: rgba(16,185,129,.16); color: #6ee7b7; }
    html.dark .gs-ico-customer { background: rgba(59,130,246,.16); color: #93c5fd; }
    html.dark .gs-ico-order    { background: rgba(249,115,22,.16); color: #fdba74; }
    html.dark .gs-ico-zone     { background: rgba(139,92,246,.18); color: #c4b5fd; }
    html.dark .gs-ico-coupon   { background: rgba(236,72,153,.16); color: #f9a8d4; }
    html.dark .gs-ico-recent   { background: #1f2937; color: #6b7280; }

    .gs-text { min-width: 0; flex: 1 1 auto; }
    .gs-title {
        display: block;
        font-size: 13.5px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .gs-title mark { background: rgba(79,70,229,.18); color: inherit; border-radius: 3px; padding: 0 1px; }
    html.dark .gs-title { color: #f1f5f9; }
    html.dark .gs-title mark { background: rgba(129,140,248,.32); }
    .gs-meta {
        display: block;
        font-size: 11.5px;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .gs-go { width: 14px; height: 14px; flex: none; color: #cbd5e1; opacity: 0; transition: opacity .14s ease; }
    .gs-item-on .gs-go { opacity: 1; }
    [dir="rtl"] .gs-go { transform: scaleX(-1); }

    .gs-state {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 26px 16px;
        font-size: 13px;
        color: #94a3b8;
    }
    .gs-empty { flex-direction: column; gap: 0; padding: 30px 16px; color: #64748b; }
    html.dark .gs-empty { color: #9ca3af; }
    .gs-spin { animation: gs-spin .8s linear infinite; }
    @keyframes gs-spin { to { transform: rotate(360deg); } }

    .gs-hint { padding: 14px 14px 16px; }
    .gs-hint-lead { font-size: 12.5px; color: #64748b; }
    html.dark .gs-hint-lead { color: #9ca3af; }
    .gs-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .gs-chip {
        padding: 3px 9px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }
    html.dark .gs-chip { border-color: #374151; background: #1f2937; color: #9ca3af; }

    .gs-foot {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 8px 12px;
        border-top: 1px solid #f1f5f9;
        background: #fbfcfe;
        font-size: 10.5px;
        color: #94a3b8;
    }
    html.dark .gs-foot { border-top-color: #1f2937; background: #0f172a; color: #6b7280; }
    .gs-foot kbd {
        display: inline-block;
        min-width: 16px;
        margin-inline-end: 3px;
        padding: 1px 4px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-family: inherit;
        font-size: 10px;
        text-align: center;
    }
    html.dark .gs-foot kbd { border-color: #374151; background: #1f2937; }

    /* ---- mobile: the field takes over the header row ---- */
    .gs-field-mobile {
        position: fixed;
        z-index: 40;
        top: 10px;
        inset-inline: 12px;
    }
    .gs-field-mobile .gs-box { display: flex; width: 100%; background: #fff; }
    html.dark .gs-field-mobile .gs-box { background: #111827; }
    .gs-field-mobile .gs-panel { width: 100%; }
    .gs-backdrop { position: fixed; inset: 0; z-index: 30; background: rgba(15,23,42,.45); backdrop-filter: blur(2px); }

    @media (prefers-reduced-motion: reduce) {
        .gs-box, .gs-item, .gs-go, .gs-clear { transition: none; }
        .gs-panel { animation: none; }
        .gs-spin { animation-duration: 2s; }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('rrSearch', function () {
            return {
                q: '',
                groups: [],
                flat: [],
                cursor: -1,
                loading: false,
                isOpen: false,
                mobile: false,
                recent: [],
                metaKey: '⌘',
                _timer: null,
                _ctrl: null,

                init() {
                    this.metaKey = /Mac|iPhone|iPad/.test(navigator.platform || '') ? '⌘' : 'Ctrl+';
                    try {
                        this.recent = JSON.parse(localStorage.getItem('rr_search_recent') || '[]');
                    } catch (e) {
                        this.recent = [];
                    }
                    // ⌘K / Ctrl+K from anywhere, as long as focus is not in another field
                    window.addEventListener('keydown', (e) => {
                        if ((e.metaKey || e.ctrlKey) && (e.key || '').toLowerCase() === 'k') {
                            e.preventDefault();
                            this.focusInput();
                        }
                    });
                },

                focusInput() {
                    // below md the field is collapsed behind the icon
                    if (window.matchMedia('(max-width: 767px)').matches) {
                        this.openMobile();
                        return;
                    }
                    this.isOpen = true;
                    this.$nextTick(() => this.$refs.input.focus());
                },

                openMobile() {
                    this.mobile = true;
                    this.isOpen = true;
                    this.$nextTick(() => this.$refs.input.focus());
                },

                close() {
                    this.isOpen = false;
                    this.mobile = false;
                    this.cursor = -1;
                },

                reset() {
                    this.q = '';
                    this.groups = [];
                    this.flat = [];
                    this.cursor = -1;
                    this.loading = false;
                    clearTimeout(this._timer);
                },

                onInput() {
                    clearTimeout(this._timer);
                    const q = this.q.trim();
                    if (q.length < 2) {
                        this.groups = [];
                        this.flat = [];
                        this.cursor = -1;
                        this.loading = false;
                        return;
                    }
                    this.isOpen = true;
                    this.loading = true;
                    // typing is faster than the round trip; only the last keystroke queries
                    this._timer = setTimeout(() => this.run(q), 220);
                },

                async run(q) {
                    if (this._ctrl) this._ctrl.abort();
                    this._ctrl = new AbortController();
                    try {
                        const res = await fetch(@json(route('admin.search')) + '?q=' + encodeURIComponent(q), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            signal: this._ctrl.signal,
                        });
                        if (!res.ok) throw new Error(res.status);
                        const data = await res.json();
                        // a slower earlier response must not overwrite a newer query
                        if (this.q.trim() !== q) return;
                        this.groups = data.groups || [];
                        this.flat = this.groups.reduce((a, g) => a.concat(g.items), []);
                        this.cursor = this.flat.length ? 0 : -1;
                        this.loading = false;
                    } catch (e) {
                        if (e.name === 'AbortError') return;   // superseded, keep loading state
                        this.groups = [];
                        this.flat = [];
                        this.loading = false;
                    }
                },

                index(gi, ii) {
                    let n = 0;
                    for (let i = 0; i < gi; i++) n += this.groups[i].items.length;
                    return n + ii;
                },

                move(step) {
                    if (!this.flat.length) return;
                    this.cursor = (this.cursor + step + this.flat.length) % this.flat.length;
                    this.$nextTick(() => {
                        const el = this.$refs.scroll && this.$refs.scroll.querySelector('[data-i="' + this.cursor + '"]');
                        if (el) el.scrollIntoView({ block: 'nearest' });
                    });
                },

                go(item) {
                    if (!item) return;
                    this.remember(item);
                    window.location.href = item.url;
                },

                remember(item) {
                    const next = [{ title: item.title, meta: item.meta, url: item.url }]
                        .concat(this.recent.filter((r) => r.url !== item.url))
                        .slice(0, 5);
                    this.recent = next;
                    try { localStorage.setItem('rr_search_recent', JSON.stringify(next)); } catch (e) {}
                },

                clearRecent() {
                    this.recent = [];
                    try { localStorage.removeItem('rr_search_recent'); } catch (e) {}
                },

                // escape first, then wrap the matched run — never trust the value as HTML
                highlight(text) {
                    const safe = String(text).replace(/[&<>"']/g, (c) => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                    }[c]));
                    const q = this.q.trim();
                    if (q.length < 2) return safe;
                    const needle = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    return safe.replace(new RegExp('(' + needle + ')', 'ig'), '<mark>$1</mark>');
                },

                iconPath(key) {
                    return {
                        nav: 'M4 6h16M4 12h16M4 18h16',
                        driver: 'M5 17h14M5 17a2 2 0 11-4 0 2 2 0 014 0zm14 0a2 2 0 104 0 2 2 0 00-4 0zM3 17V9l2-4h11l3 4h2v8',
                        customer: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        order: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                        zone: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
                        coupon: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z',
                    }[key] || 'M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z';
                },
            };
        });
    });
</script>
@endpush
@endonce
