@props(['admin'])

@php
    $role = $admin->getRoleNames()->first() ?? 'Administrator';
    $initials = collect(explode(' ', trim($admin->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $avatar = $admin->avatar ? asset('storage/' . $admin->avatar) : null;
    $iconButton = 'relative inline-flex size-10 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-ring dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100';
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 dark:border-gray-800 dark:bg-gray-900/90">
    <button type="button" @click="$store.sidebar.toggle()" class="{{ $iconButton }}" aria-controls="admin-sidebar">
        <span class="sr-only">Toggle navigation</span><i class="bi bi-list text-xl" aria-hidden="true"></i>
    </button>

    {{-- Global search (opens the command palette) --}}
    <button type="button" @click="$dispatch('open-command-palette')"
            class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm text-gray-500 transition-colors hover:border-gray-300 focus-ring sm:max-w-md dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-400 dark:hover:border-gray-700">
        <i class="bi bi-search" aria-hidden="true"></i>
        <span class="truncate">Search orders, products, customers…</span>
        <kbd class="ml-auto hidden rounded border border-gray-300 bg-white px-1.5 py-0.5 font-sans text-[11px] font-medium text-gray-500 sm:inline dark:border-gray-700 dark:bg-gray-900">Ctrl K</kbd>
    </button>

    <div class="ml-auto flex items-center gap-1">
        <a href="{{ url('/en') }}" target="_blank" rel="noopener" class="{{ $iconButton }} max-sm:hidden" title="View storefront">
            <span class="sr-only">View storefront (opens in a new tab)</span><i class="bi bi-shop" aria-hidden="true"></i>
        </a>

        <button type="button" x-data @click="$store.theme.toggle()" class="{{ $iconButton }}"
                :title="$store.theme.supported ? ($store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode') : 'Dark mode is available on redesigned pages'">
            <span class="sr-only" x-text="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">Toggle dark mode</span>
            <i class="bi" :class="$store.theme.dark ? 'bi-sun' : 'bi-moon-stars'" aria-hidden="true"></i>
        </button>

        {{-- Notifications --}}
        <div class="relative" x-data="adminBell({{ $admin->unreadNotifications()->count() }})" x-init="start()" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="toggle()" class="{{ $iconButton }}" :aria-expanded="open.toString()" aria-haspopup="true">
                <span class="sr-only" x-text="unread ? unread + ' unread notifications' : 'Notifications'">Notifications</span>
                <i class="bi" :class="unread ? 'bi-bell-fill text-brand-600 dark:text-brand-400' : 'bi-bell'" aria-hidden="true"></i>
                <span x-show="unread" x-cloak x-text="unread > 99 ? '99+' : unread"
                      class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white ring-2 ring-white dark:ring-gray-900" aria-hidden="true"></span>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</p>
                    <button type="button" x-show="unread" @click="markAllRead()" class="rounded text-xs font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Mark all as read</button>
                </div>
                <div class="scrollbar-thin max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                    <template x-for="item in items" :key="item.id">
                        <a :href="item.open_url" class="flex gap-3 px-4 py-3 transition-colors hover:bg-gray-50 focus-ring dark:hover:bg-gray-800/60">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg" aria-hidden="true"
                                  :class="{
                                      'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400': item.color === 'emerald',
                                      'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400': item.color === 'amber',
                                      'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400': item.color === 'red',
                                      'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400': item.color === 'indigo',
                                      'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': !['emerald', 'amber', 'red', 'indigo'].includes(item.color),
                                  }">
                                <i class="bi" :class="item.icon"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-medium text-gray-900 dark:text-white" :class="item.read && 'font-normal text-gray-600 dark:text-gray-400'" x-text="item.title"></span>
                                    <span x-show="!item.read" class="size-2 shrink-0 rounded-full bg-brand-600 dark:bg-brand-400"><span class="sr-only">Unread</span></span>
                                </span>
                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="item.body"></span>
                                <span class="mt-0.5 block text-[11px] text-gray-400 dark:text-gray-500" x-text="item.time"></span>
                            </span>
                        </a>
                    </template>
                    <div x-show="!loaded" class="p-4"><x-admin.skeleton type="avatar" /></div>
                    <p x-show="loaded && !items.length" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">You're all caught up.</p>
                </div>
                <a href="{{ route('admin.notifications.index') }}" class="block border-t border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-600 hover:bg-gray-50 focus-ring dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800/60">View all notifications</a>
            </div>
        </div>

        {{-- Profile --}}
        <div class="relative ml-1" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                    class="flex items-center gap-3 rounded-lg p-1 pr-2 transition-colors hover:bg-gray-100 focus-ring dark:hover:bg-gray-800">
                @if($avatar)
                    <img src="{{ $avatar }}" alt="" class="size-8 rounded-full object-cover">
                @else
                    <span class="flex size-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300" aria-hidden="true">{{ $initials }}</span>
                @endif
                <span class="hidden text-left leading-tight md:block">
                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $admin->name }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $role }}</span>
                </span>
                <i class="bi bi-chevron-down hidden text-xs text-gray-400 md:block" aria-hidden="true"></i>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $admin->name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $admin->email }}</p>
                    <x-admin.status-badge :label="$role" tone="brand" :dot="false" class="mt-2" />
                </div>
                @php($menuLink = 'flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus-ring dark:text-gray-300 dark:hover:bg-gray-800/60')
                <a href="{{ route('admin.profile') }}" class="{{ $menuLink }}"><i class="bi bi-person text-gray-400" aria-hidden="true"></i> Your profile</a>
                <a href="{{ route('admin.notifications.index') }}" class="{{ $menuLink }}"><i class="bi bi-bell text-gray-400" aria-hidden="true"></i> Notifications</a>
                @can('settings.view')
                    <a href="{{ route('admin.settings.index') }}" class="{{ $menuLink }}"><i class="bi bi-gear text-gray-400" aria-hidden="true"></i> Store settings</a>
                @endcan
                <div class="my-1 border-t border-gray-200 dark:border-gray-800"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="{{ $menuLink }} w-full text-red-600 dark:text-red-400"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
