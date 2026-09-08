{{-- Collapsible admin sidebar, theme-aware (RTL-aware) --}}
@php($adminLogo = \App\Models\SystemSetting::get('admin_logo'))
@php($navOrder = adminUser()?->nav_order ?? null)

<aside
    class="fixed inset-y-0 start-0 z-40 flex w-64 flex-col border-e border-gray-200 bg-white text-gray-700 transition-transform duration-300 ease-out lg:!translate-x-0 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full'">

    {{-- Brand --}}
    <div class="shrink-0 px-4 pb-3 pt-4">
        <div class="flex items-center gap-2.5">
            <img src="{{ $adminLogo ? \Illuminate\Support\Facades\Storage::url($adminLogo) : asset('images/logo.png') }}"
                 alt="{{ appName() }}" class="h-10 w-10 shrink-0 rounded-xl object-contain">
            <p class="min-w-0 flex-1 truncate text-[17px] font-extrabold leading-tight text-gray-900 dark:text-gray-50">{{ appName() }}</p>
            <button type="button" @click="sidebarOpen = false"
                    class="shrink-0 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 lg:hidden dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    aria-label="{{ __('admin.close') }}">
                <svg class="h-5 w-5 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        <a href="{{ route('landing') }}" target="_blank" rel="noopener"
           class="mt-3.5 flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/25 dark:hover:text-indigo-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            {{ __('admin.view_website') }}
        </a>
    </div>

    {{-- Navigation --}}
    <nav id="admin-sidebar-nav" class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">

        {{-- The reorder icon hints that these rows can be dragged. --}}
        <p class="flex items-center gap-2 px-3 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-[.09em] text-gray-400 dark:text-gray-500">
            {{ __('admin.menu') }}
            <svg class="ms-auto h-3.5 w-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('admin.drag_to_reorder') }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L4 7m3-3l3 3m7 1v12m0 0l3-3m-3 3l-3-3"/>
            </svg>
        </p>

        {{-- Overview --}}
        <div class="space-y-0.5" data-nav-section="overview">
            <x-admin.nav-link data-nav-key="dashboard" :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" tone="text-indigo-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </x-slot:icon>
                {{ __('admin.dashboard') }}
            </x-admin.nav-link>

            @if (adminCan('reports', 'read'))
            <x-admin.nav-group data-nav-key="reports" title="{{ __('admin.reports') }}" tone="text-sky-500"
                               :open="request()->routeIs('admin.reports.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.reports.revenue')" :active="request()->routeIs('admin.reports.revenue')">{{ __('admin.revenue') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.reports.drivers')" :active="request()->routeIs('admin.reports.drivers')">{{ __('admin.drivers') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.reports.orders')" :active="request()->routeIs('admin.reports.orders')">{{ __('admin.orders') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.reports.customers')" :active="request()->routeIs('admin.reports.customers')">{{ __('admin.customers') }}</x-admin.nav-child>
            </x-admin.nav-group>
            @endif
        </div>

        {{-- Ride & operations --}}
        @if (adminCan('drivers', 'read') || adminCan('users', 'read') || adminCan('orders', 'read') || adminCan('settings', 'read') || adminCan('sos', 'read'))
        <x-admin.nav-section :title="__('admin.nav_ride_operations')" section-key="ride">
            @if (adminCan('drivers', 'read'))
            <x-admin.nav-group data-nav-key="drivers" title="{{ __('admin.drivers') }}" tone="text-amber-500"
                               :open="request()->routeIs('admin.drivers.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.drivers.index')" :active="request()->routeIs('admin.drivers.index') || request()->routeIs('admin.drivers.show') || request()->routeIs('admin.drivers.edit')">{{ __('admin.all_drivers') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.drivers.pending')" :active="request()->routeIs('admin.drivers.pending') || request()->routeIs('admin.drivers.review')">{{ __('admin.pending') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.drivers.expiring-documents')" :active="request()->routeIs('admin.drivers.expiring-documents')">{{ __('admin.expiring_docs') }}</x-admin.nav-child>
            </x-admin.nav-group>
            @endif

            @if (adminCan('users', 'read'))
            <x-admin.nav-group data-nav-key="customers" title="{{ __('admin.customers') }}" tone="text-violet-500"
                               :open="request()->routeIs('admin.customers.*','admin.referrals.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                </x-slot:icon>
                {{-- "All" stays highlighted for any customers screen except the
                     Blocked quick-filter, which owns its own row. --}}
                <x-admin.nav-child :href="route('admin.customers.index')"
                                   :active="request()->routeIs('admin.customers.*') && request()->input('status') !== 'blocked'">{{ __('admin.all_customers') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.customers.index', ['status' => 'blocked'])"
                                   :active="request()->routeIs('admin.customers.index') && request()->input('status') === 'blocked'">{{ __('admin.blocked') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.referrals.index')" :active="request()->routeIs('admin.referrals.*')">{{ __('admin.referrals') }}</x-admin.nav-child>
            </x-admin.nav-group>
            @endif

            @if (adminCan('orders', 'read'))
            <x-admin.nav-group data-nav-key="orders" title="{{ __('admin.orders') }}" tone="text-blue-500"
                               :open="request()->routeIs('admin.orders.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.show')">{{ __('admin.all_orders') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.orders.scheduled')" :active="request()->routeIs('admin.orders.scheduled')">{{ __('admin.scheduled') }}</x-admin.nav-child>
            </x-admin.nav-group>
            @endif

            @if (adminCan('settings', 'read'))
            <x-admin.nav-link data-nav-key="zones" :href="route('admin.zones.index')" :active="request()->routeIs('admin.zones.*')" tone="text-emerald-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </x-slot:icon>
                {{ __('admin.zones') }}
            </x-admin.nav-link>

            {{-- Pricing Settings intentionally omitted: it now lives as a tab
                 under Settings, and listing it twice double-highlights. --}}
            <x-admin.nav-group data-nav-key="services-pricing" title="{!! __('admin.services_pricing') !!}" tone="text-orange-500"
                               :open="request()->routeIs('admin.services.*','admin.vehicle-categories.*','admin.parcel-pricing.*','admin.surge-pricing.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.services.index')" :active="request()->routeIs('admin.services.*')">{{ __('admin.services') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.vehicle-categories.index')" :active="request()->routeIs('admin.vehicle-categories.*')">{{ __('admin.vehicle_categories') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.parcel-pricing.index')" :active="request()->routeIs('admin.parcel-pricing.*')">{{ __('admin.parcel_pricing') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.surge-pricing.index')" :active="request()->routeIs('admin.surge-pricing.*')">{{ __('admin.surge_pricing') }}</x-admin.nav-child>
            </x-admin.nav-group>
            @endif

            @if (adminCan('sos', 'read'))
            <x-admin.nav-link data-nav-key="sos" :href="route('admin.sos.index')" :active="request()->routeIs('admin.sos.*')" tone="text-red-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </x-slot:icon>
                {{ __('admin.sos_alerts') }}
            </x-admin.nav-link>
            @endif
        </x-admin.nav-section>
        @endif

        {{-- Finance --}}
        @if (adminCan('payments', 'read') || adminCan('settings', 'read'))
        <x-admin.nav-section :title="__('admin.nav_finance')" section-key="finance">
            @if (adminCan('payments', 'read'))
            <x-admin.nav-group data-nav-key="payments" title="{{ __('admin.payments') }}" tone="text-emerald-500"
                               :open="request()->routeIs('admin.payments.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.payments.transactions')" :active="request()->routeIs('admin.payments.transactions')">{{ __('admin.transactions') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.payments.withdrawals')" :active="request()->routeIs('admin.payments.withdrawals')">{{ __('admin.withdrawals') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.payments.refunds')" :active="request()->routeIs('admin.payments.refunds')">{{ __('admin.refunds') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.payments.cod-reconciliation')" :active="request()->routeIs('admin.payments.cod-reconciliation')">{{ __('admin.cod_reconciliation') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.payments.driver-dues')" :active="request()->routeIs('admin.payments.driver-dues')">{{ __('admin.driver_dues') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.payments.customer-dues')" :active="request()->routeIs('admin.payments.customer-dues')">{{ __('admin.customer_dues') }}</x-admin.nav-child>
                @if (adminCan('reports', 'read'))
                    <x-admin.nav-child :href="route('admin.payments.revenue')" :active="request()->routeIs('admin.payments.revenue')">{{ __('admin.revenue') }}</x-admin.nav-child>
                @endif
            </x-admin.nav-group>
            @endif

            @if (adminCan('settings', 'read'))
            <x-admin.nav-link data-nav-key="coupons" :href="route('admin.coupons.index')" :active="request()->routeIs('admin.coupons.*')" tone="text-rose-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                </x-slot:icon>
                {{ __('admin.coupons') }}
            </x-admin.nav-link>
            @endif
        </x-admin.nav-section>
        @endif

        {{-- Marketing & content --}}
        @if (adminCan('settings', 'read'))
        <x-admin.nav-section :title="__('admin.nav_marketing_content')" section-key="marketing">
            {{-- history has its own entry under Activity --}}
            <x-admin.nav-link data-nav-key="notifications-broadcast" :href="route('admin.notifications.broadcast')" tone="text-purple-500"
                              :active="request()->routeIs('admin.notifications.broadcast')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </x-slot:icon>
                {{ __('admin.notifications') }}
            </x-admin.nav-link>

            <x-admin.nav-link data-nav-key="banners" :href="route('admin.banners.index')" :active="request()->routeIs('admin.banners.*')" tone="text-fuchsia-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </x-slot:icon>
                {{ __('admin.banners') }}
            </x-admin.nav-link>

        </x-admin.nav-section>
        @endif

        {{-- Support --}}
        @if (adminCan('settings', 'read') || adminCan('disputes', 'read'))
        <x-admin.nav-section :title="__('admin.nav_support')" section-key="support">
            <x-admin.nav-group data-nav-key="help-safety" title="{!! __('admin.help_safety') !!}" tone="text-teal-500"
                               :open="request()->routeIs('admin.safety-tips.*','admin.disputes.*','admin.landing-page.*','admin.pages.*')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </x-slot:icon>
                @if (adminCan('settings', 'read'))
                    <x-admin.nav-child :href="route('admin.landing-page.index')" :active="request()->routeIs('admin.landing-page.*')">{{ __('admin.faqs') }}</x-admin.nav-child>
                    <x-admin.nav-child :href="route('admin.safety-tips.index')" :active="request()->routeIs('admin.safety-tips.*')">{{ __('admin.safety_tips') }}</x-admin.nav-child>
                    <x-admin.nav-child :href="route('admin.pages.index')" :active="request()->routeIs('admin.pages.*')">{{ __('admin.pages') }}</x-admin.nav-child>
                @endif
                @if (adminCan('disputes', 'read'))
                    <x-admin.nav-child :href="route('admin.disputes.index')" :active="request()->routeIs('admin.disputes.*')">{{ __('admin.complaints') }}</x-admin.nav-child>
                @endif
            </x-admin.nav-group>
        </x-admin.nav-section>
        @endif

        {{-- Activity --}}
        <x-admin.nav-section :title="__('admin.nav_activity')" section-key="activity">
            <x-admin.nav-link data-nav-key="login-history" :href="route('admin.login-history')" :active="request()->routeIs('admin.login-history')" tone="text-teal-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot:icon>
                {{ __('admin.login_history') }}
            </x-admin.nav-link>

            @if (adminCan('settings', 'read'))
            <x-admin.nav-link data-nav-key="notifications-history" :href="route('admin.notifications.history')" :active="request()->routeIs('admin.notifications.history')" tone="text-cyan-500">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-6 4h6m-6-4h.01M9 16h.01"/></svg>
                </x-slot:icon>
                {{ __('admin.notification_history') }}
            </x-admin.nav-link>
            @endif
        </x-admin.nav-section>

        {{-- System --}}
        @if (adminCan('settings', 'read'))
        <x-admin.nav-section :title="__('admin.nav_system')" section-key="system">
            {{-- General opens the tabbed settings hub (Business, Map, Payment,
                 Notifications, Languages, Sub-Admins, Advanced, Pricing). --}}
            <x-admin.nav-group data-nav-key="settings" title="{{ __('admin.settings') }}" tone="text-slate-400"
                               :open="request()->routeIs('admin.settings.*','admin.languages.*','admin.sub-admins.*','admin.website.*','admin.profile','admin.two-factor.setup','admin.setup-guide')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </x-slot:icon>
                <x-admin.nav-child :href="route('admin.settings.general')"
                                   :active="request()->routeIs('admin.settings.*','admin.languages.*','admin.sub-admins.*')">{{ __('admin.general') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.website.index')" :active="request()->routeIs('admin.website.*')">{{ __('admin.website_cms') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.profile')" :active="request()->routeIs('admin.profile')">{{ __('admin.my_profile') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.two-factor.setup')" :active="request()->routeIs('admin.two-factor.setup')">{{ __('admin.security_2fa') }}</x-admin.nav-child>
                <x-admin.nav-child :href="route('admin.setup-guide')" :active="request()->routeIs('admin.setup-guide')">{{ __('admin.setup_guide') }}</x-admin.nav-child>
            </x-admin.nav-group>
        </x-admin.nav-section>
        @endif

        {{-- shown once the admin has a saved order of their own --}}
        <div id="nav-reset-wrap" class="pt-4 {{ $navOrder ? '' : 'hidden' }}">
            <button type="button" id="nav-reset"
                class="flex w-full items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-[11px] font-semibold text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('admin.reset_menu_order') }}
            </button>
        </div>

    </nav>
</aside>

@once
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        (function () {
            var nav = document.getElementById('admin-sidebar-nav');
            if (!nav || typeof Sortable === 'undefined') return;

            var saved = @json($navOrder ?: (object) []);
            var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            var resetWrap = document.getElementById('nav-reset-wrap');

            function sections() {
                return Array.prototype.slice.call(nav.querySelectorAll('[data-nav-section]'));
            }

            // Apply the stored order. Keys the admin can no longer see (permission
            // removed, feature gone) are simply absent; anything not in the saved
            // list keeps its default position at the end.
            sections().forEach(function (box) {
                var order = saved[box.dataset.navSection];
                if (!Array.isArray(order) || !order.length) return;

                order.slice().reverse().forEach(function (key) {
                    var el = box.querySelector(':scope > [data-nav-key="' + CSS.escape(key) + '"]');
                    if (el) box.insertBefore(el, box.firstElementChild);
                });
            });

            function collect() {
                var out = {};
                sections().forEach(function (box) {
                    out[box.dataset.navSection] = Array.prototype.slice
                        .call(box.children)
                        .map(function (el) { return el.dataset.navKey; })
                        .filter(Boolean);
                });
                return out;
            }

            function post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: body ? JSON.stringify(body) : null,
                });
            }

            sections().forEach(function (box) {
                Sortable.create(box, {
                    handle: '.nav-grip',
                    draggable: '[data-nav-key]',
                    animation: 170,
                    ghostClass: 'nav-drag-ghost',
                    chosenClass: 'nav-drag-chosen',
                    fallbackOnBody: true,
                    onEnd: function () {
                        post('{{ route('admin.nav-order.store') }}', { order: collect() });
                        resetWrap?.classList.remove('hidden');
                    },
                });
            });

            document.getElementById('nav-reset')?.addEventListener('click', function () {
                post('{{ route('admin.nav-order.reset') }}').then(function () {
                    window.location.reload();
                });
            });
        })();
    </script>
    @endpush
@endonce
