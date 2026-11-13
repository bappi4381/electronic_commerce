{{--
    The only sidebar markup: an off-canvas drawer below lg, a fixed rail (expanded or collapsed) from lg up.
    Items come from config/admin_navigation.php and are filtered by the admin's permissions.
--}}
@props(['navigation'])

<div x-data>
    <div x-show="$store.sidebar.mobileOpen" x-transition.opacity x-cloak @click="$store.sidebar.close()"
         class="fixed inset-0 z-40 bg-gray-950/50 lg:hidden" aria-hidden="true"></div>

    <aside id="admin-sidebar" aria-label="Main navigation"
           :data-open="$store.sidebar.mobileOpen"
           @keydown.escape.window="$store.sidebar.close()"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-gray-200 bg-white transition-[transform,width] duration-200 max-lg:-translate-x-full max-lg:data-[open=true]:translate-x-0 lg:w-64 sidebar-collapsed:lg:w-[4.5rem] dark:border-gray-800 dark:bg-gray-900">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 px-4 dark:border-gray-800">
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-ring">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white dark:bg-brand-500 dark:text-gray-950" aria-hidden="true">
                    <i class="bi bi-lightning-charge-fill"></i>
                </span>
                <span class="truncate text-base font-semibold tracking-tight text-gray-900 sidebar-collapsed:lg:hidden dark:text-white">
                    {{ \App\Models\Setting::get('site_name', 'ONEMALL') }}
                </span>
            </a>
            <button type="button" @click="$store.sidebar.close()" class="ml-auto rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus-ring lg:hidden dark:hover:bg-gray-800">
                <span class="sr-only">Close navigation</span><i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="scrollbar-thin flex-1 space-y-6 overflow-y-auto px-3 py-4">
            @foreach($navigation as $group)
                <div>
                    @if($group['label'])
                        <p class="mb-1 px-3 text-xs font-medium uppercase tracking-wider text-gray-400 sidebar-collapsed:lg:sr-only dark:text-gray-500">{{ $group['label'] }}</p>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif
                                   :title="$store.sidebar.collapsed ? @js($item['title']) : null"
                                   @class([
                                       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-ring sidebar-collapsed:lg:justify-center sidebar-collapsed:lg:px-0',
                                       'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $item['active'],
                                       'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' => !$item['active'],
                                   ])>
                                    <i @class(['bi text-base', $item['icon'], 'text-brand-600 dark:text-brand-400' => $item['active'], 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300' => !$item['active']]) aria-hidden="true"></i>
                                    <span class="truncate sidebar-collapsed:lg:sr-only">{{ $item['title'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="hidden shrink-0 border-t border-gray-200 p-3 lg:block dark:border-gray-800">
            <button type="button" @click="$store.sidebar.toggle()" aria-controls="admin-sidebar" :aria-expanded="(!$store.sidebar.collapsed).toString()"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-ring sidebar-collapsed:justify-center sidebar-collapsed:px-0 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100">
                <i class="bi bi-chevron-double-left sidebar-collapsed:rotate-180" aria-hidden="true"></i>
                <span class="sidebar-collapsed:sr-only">Collapse sidebar</span>
            </button>
        </div>
    </aside>
</div>
