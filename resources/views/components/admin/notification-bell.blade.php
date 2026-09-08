{{-- Admin notification bell: polls the JSON feed, shows unread count + last 5 --}}
<div class="relative" x-data="{
        open: false,
        unread: 0,
        items: [],
        async load() {
            try {
                const res = await fetch('{{ route('admin.notifications.list') }}', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.unread = data.unread; this.items = data.items;
            } catch (e) {}
        },
        async markRead() {
            try {
                await fetch('{{ route('admin.notifications.mark-read') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                this.unread = 0;
            } catch (e) {}
        }
    }"
    x-init="load(); setInterval(() => load(), 30000)">

    <button type="button" @click="open = !open; if (open) markRead()" class="relative rounded-lg p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span x-show="unread > 0" x-cloak class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] rounded-full bg-red-600 text-white text-[10px] font-bold px-1" x-text="unread > 9 ? '9+' : unread"></span>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
         class="absolute end-0 mt-2 w-80 rounded-lg bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-30">
        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('admin.notifications') }}</p>
        </div>
        <template x-if="items.length === 0">
            <p class="px-4 py-6 text-center text-sm text-gray-400">{{ __('admin.no_new_notifications') }}</p>
        </template>
        <template x-for="item in items" :key="item.id">
            <div class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-50 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="item.title"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="item.body"></p>
            </div>
        </template>
        <a href="{{ route('admin.notifications.history') }}" class="block px-4 py-2 text-center text-xs text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('admin.see_all') }}</a>
    </div>
</div>
