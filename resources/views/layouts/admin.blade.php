@php($adminLocale = app()->getLocale())
@php($adminRtl = adminIsRtl())
@php($adminTheme = request()->cookie('admin_theme', 'light'))
@php($adminDark = $adminTheme === 'dark')
<!DOCTYPE html>
<html lang="{{ $adminLocale }}"
      dir="{{ $adminRtl ? 'rtl' : 'ltr' }}"
      class="min-h-full bg-gray-50 dark:bg-gray-900 {{ $adminDark ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ appName() }}</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Tailwind CDN: enable class-based dark mode --}}
    <script>tailwind.config = { darkMode: 'class' };</script>
    {{-- Theme 'system' can only be resolved on the client. Runs before paint so
         the OS preference never shows as a flash of the wrong theme. --}}
    <script>
        window.rrTheme = @json($adminTheme);
        (function () {
            if (window.rrTheme !== 'system') return;
            var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    {{-- collapse plugin must load before Alpine core; powers the smooth
         height animation on the sidebar's nav groups --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if ($adminRtl)
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>body{font-family:'Cairo',sans-serif;}</style>
    @endif
    <style>
        html.dark { color-scheme: dark; }
        #admin-sidebar-nav { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        #admin-sidebar-nav::-webkit-scrollbar { width: 6px; }
        #admin-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        #admin-sidebar-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        #admin-sidebar-nav::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        html.dark #admin-sidebar-nav { scrollbar-color: #4b5563 transparent; }
        html.dark #admin-sidebar-nav::-webkit-scrollbar-thumb { background: #4b5563; }
        html.dark #admin-sidebar-nav::-webkit-scrollbar-thumb:hover { background: #6b7280; }

        /* ---- sidebar nav motion ---- */
        .nav-item,
        .nav-child {
            transition: background-color .18s ease, color .18s ease, transform .18s cubic-bezier(.22,.68,.34,1);
        }
        .nav-item:hover,
        .nav-child:hover { transform: translateX(2px); }
        [dir="rtl"] .nav-item:hover,
        [dir="rtl"] .nav-child:hover { transform: translateX(-2px); }

        .nav-ico { transition: transform .22s cubic-bezier(.34,1.4,.44,1), color .18s ease; }
        .nav-item:hover .nav-ico { transform: scale(1.12); }

        /* Active row is a solid brand pill — it reads as the current page at a
           glance, which a tint alone did not do once the list got long. */
        .nav-item-active {
            background: #4f46e5;
            color: #fff;
            box-shadow: 0 6px 16px -6px rgba(79,70,229,.7);
        }
        .nav-item-active:hover { background: #4338ca; color: #fff; }
        html.dark .nav-item-active { background: #6366f1; box-shadow: 0 6px 16px -6px rgba(99,102,241,.55); }
        html.dark .nav-item-active:hover { background: #4f46e5; }

        /* Sub-item keeps the lighter treatment so it never competes with the
           parent pill. */
        .nav-child-active {
            background: rgba(79,70,229,.09);
            color: #4338ca;
            font-weight: 600;
        }
        html.dark .nav-child-active { background: rgba(99,102,241,.18); color: #c7d2fe; }

        .nav-dot {
            width: 5px;
            height: 5px;
            border-radius: 999px;
            flex: none;
            transition: background-color .18s ease, transform .18s ease;
        }
        .nav-child:hover .nav-dot { transform: scale(1.4); }

        /* ---- drag handle + drag states ---- */
        /* Absolutely placed so it never steals width from the label — it fades
           in over the chevron (or the row's end) only while hovering. */
        .nav-grip {
            position: absolute;
            inset-inline-end: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            color: #cbd5e1;
            cursor: grab;
            opacity: 0;
            transition: opacity .18s ease, color .18s ease;
            touch-action: none;
        }
        /* Stays grabbable even at opacity 0 — a hover-gated pointer-events would
           make it unreachable on touch, where there is no hover. A click that
           isn't a drag still bubbles to the row, so nothing is swallowed. */
        html.dark .nav-grip { color: #4b5563; }
        .nav-item:hover .nav-grip { opacity: 1; }
        /* swap: the chevron steps aside for the grip */
        .nav-item:hover > svg:not(.nav-grip svg) { opacity: 0; }
        .nav-grip:hover { color: #6366f1; }
        .nav-grip:active { cursor: grabbing; }

        .nav-drag-ghost { opacity: .35; }
        .nav-drag-chosen .nav-item,
        .nav-drag-chosen.nav-item {
            background: rgba(99,102,241,.10);
            box-shadow: 0 8px 20px -8px rgba(49,46,129,.35);
        }
        /* the lift transform would fight the drag translation */
        .nav-drag-chosen .nav-item:hover,
        .nav-drag-chosen.nav-item:hover { transform: none; }

        @media (hover: none) {
            .nav-grip { opacity: .55; }
        }

        /* ---- data tables ---- */
        /* stays on the brand indigo — a single hue, just varied in weight */
        .rr-table thead tr {
            background-image: linear-gradient(90deg,
                rgba(79,70,229,.16) 0%,
                rgba(79,70,229,.10) 55%,
                rgba(79,70,229,.05) 100%);
        }
        html.dark .rr-table thead tr {
            background-image: linear-gradient(90deg,
                rgba(99,102,241,.30) 0%,
                rgba(99,102,241,.18) 55%,
                rgba(99,102,241,.08) 100%);
        }
        .rr-table thead th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #4338ca;
            white-space: nowrap;
            border-bottom: 1px solid rgba(99,102,241,.20);
        }
        html.dark .rr-table thead th { color: #c7d2fe; border-bottom-color: rgba(129,140,248,.28); }

        .rr-row { position: relative; transition: background-color .18s ease; }
        .rr-row:hover { background: linear-gradient(90deg, rgba(99,102,241,.055), rgba(99,102,241,.015)); }
        html.dark .rr-row:hover { background: linear-gradient(90deg, rgba(99,102,241,.13), rgba(99,102,241,.03)); }

        /* brand rail slides in on the row's leading edge */
        .rr-row > td:first-child::before {
            content: '';
            position: absolute;
            inset-block: 0;
            inset-inline-start: 0;
            width: 3px;
            background: #4f46e5;
            transform: scaleY(0);
            transition: transform .22s cubic-bezier(.22,.68,.34,1);
        }
        .rr-row:hover > td:first-child::before { transform: scaleY(1); }

        @media (prefers-reduced-motion: reduce) {
            .rr-row, .rr-row > td:first-child::before { transition: none; }
        }

        /* ---- dashboard motion ---- */
        @keyframes dash-in {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }
        .dash-in {
            animation: dash-in .5s cubic-bezier(.22,.68,.34,1) both;
            animation-delay: calc(var(--i, 0) * 70ms);
        }
        .dash-card {
            transition: transform .28s cubic-bezier(.22,.68,.34,1), box-shadow .28s ease, border-color .28s ease;
        }
        a.dash-card:hover,
        .dash-card:hover {
            transform: translateY(-3px);
            border-color: rgba(129,140,248,.4);
            box-shadow: 0 2px 6px rgba(49,46,129,.05), 0 16px 32px -14px rgba(49,46,129,.22);
        }
        html.dark .dash-card:hover {
            border-color: rgba(129,140,248,.32);
            box-shadow: 0 2px 8px rgba(0,0,0,.3), 0 18px 36px -16px rgba(0,0,0,.55);
        }
        .dash-ico { transition: transform .3s cubic-bezier(.34,1.4,.44,1); }
        .dash-card:hover .dash-ico { transform: scale(1.08) rotate(-4deg); }

        @media (prefers-reduced-motion: reduce) {
            .nav-item, .nav-child, .nav-ico, .nav-dot { transition: none; }
            .nav-item:hover, .nav-child:hover { transform: none; }
            .nav-item:hover .nav-ico, .nav-child:hover .nav-dot { transform: none; }
            .dash-in { animation: none; }
            .dash-card, .dash-ico { transition: none; }
            .dash-card:hover { transform: none; }
            .dash-card:hover .dash-ico { transform: none; }
        }
    </style>
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        {{-- Mobile sidebar backdrop --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"></div>

        {{-- Sidebar --}}
        <x-admin.sidebar />

        {{-- Main column --}}
        <div class="lg:ps-64 flex flex-col min-h-screen">
            <x-admin.navbar />

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <x-admin.flash />
                <x-admin.setup-alert />
                @yield('content')
            </main>

            <footer class="px-6 py-4 text-center text-xs text-gray-400 border-t border-gray-100 dark:border-gray-700">
                &copy; {{ date('Y') }} {{ appName() }} Admin Panel
            </footer>
        </div>
    </div>

    <x-admin.toast />

    <style>[x-cloak]{display:none !important;}</style>
    @stack('scripts')
</body>
</html>
