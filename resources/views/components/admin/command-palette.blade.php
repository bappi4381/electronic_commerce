{{--
    Global search: Ctrl/Cmd+K or "/" anywhere. Jumps to admin pages, searches customers live (when allowed)
    and deep-links into the order, product and customer lists using their existing search parameters.
--}}
@props(['pages', 'searches', 'lookups'])

<div x-data="commandPalette(@js(['pages' => $pages, 'searches' => $searches, 'lookups' => $lookups]))"
     @keydown.window="onGlobalKey($event)" @open-command-palette.window="open = true"
     x-show="open" x-cloak class="fixed inset-0 z-[55] overflow-y-auto" role="dialog" aria-modal="true" aria-label="Search">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-950/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"></div>

    <div class="flex min-h-full items-start justify-center p-4 pt-[10vh]">
        <div x-show="open" x-transition @keydown.escape.prevent.stop="open = false"
             class="relative w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex items-center gap-3 border-b border-gray-200 px-4 dark:border-gray-800">
                <i class="bi bi-search text-gray-400" aria-hidden="true"></i>
                <label for="command-input" class="sr-only">Search the admin</label>
                <input id="command-input" x-ref="input" x-model.debounce.200ms="query" type="text" autocomplete="off"
                       role="combobox" aria-expanded="true" aria-controls="command-results" :aria-activedescendant="results.length ? 'command-option-' + active : null"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
                       placeholder="Search pages, or type an order ID, product or customer…"
                       class="h-14 flex-1 border-0 bg-transparent text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-gray-100">
                <span x-show="loading" class="size-4 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600" aria-hidden="true"></span>
                <kbd class="rounded border border-gray-200 px-1.5 py-0.5 text-[11px] text-gray-400 dark:border-gray-700">Esc</kbd>
            </div>

            <ul id="command-results" x-ref="list" role="listbox" class="scrollbar-thin max-h-[60vh] overflow-y-auto p-2">
                <template x-for="(item, index) in results" :key="item.kind + item.url">
                    <li :id="'command-option-' + index" :data-index="index" role="option" :aria-selected="(active === index).toString()">
                        <template x-if="index === 0 || results[index - 1].kind !== item.kind">
                            <p class="px-3 pb-1 pt-3 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500" x-text="item.kind"></p>
                        </template>
                        <a :href="item.url" @mouseenter="active = index"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm"
                           :class="active === index ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-gray-700 dark:text-gray-300'">
                            <i class="bi text-gray-400" :class="item.icon" aria-hidden="true"></i>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate" x-text="item.title"></span>
                                <span x-show="item.subtitle || item.group" class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="item.subtitle || item.group"></span>
                            </span>
                            <i x-show="active === index" class="bi bi-arrow-return-left text-xs text-gray-400" aria-hidden="true"></i>
                        </a>
                    </li>
                </template>
                <li x-show="!results.length" class="px-3 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No matches.</li>
            </ul>

            <div class="flex items-center gap-4 border-t border-gray-200 px-4 py-2 text-[11px] text-gray-400 dark:border-gray-800">
                <span><kbd class="font-sans">↑</kbd> <kbd class="font-sans">↓</kbd> to navigate</span>
                <span><kbd class="font-sans">Enter</kbd> to open</span>
            </div>
        </div>
    </div>
</div>
