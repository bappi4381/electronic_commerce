{{-- Mobile full-screen search. Opened by $store.ui.openSearch(); the bottom nav hides while it is open. --}}
<div x-data="searchBox({ mode: 'overlay', initial: @js(request()->routeIs('search.index') ? (string) request('q') : '') })"
     x-show="$store.ui.searchOpen" x-cloak
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     @keydown.escape.window="$store.ui.searchOpen && $store.ui.closeSearch()"
     class="fixed inset-0 z-[var(--sf-z-overlay)] flex flex-col bg-[var(--sf-bg)] lg:hidden"
     role="dialog" aria-modal="true" aria-label="{{ __('Search') }}">
    <form action="{{ route('search.index') }}" method="GET" role="search" @submit.prevent="submit()"
          class="flex items-center gap-2 bg-navy-900 px-2 pb-2.5 pt-[max(0.625rem,env(safe-area-inset-top))]">
        <button type="button" @click="$store.ui.closeSearch()" class="flex size-11 shrink-0 items-center justify-center rounded-full text-white sf-focus">
            <span class="sr-only">{{ __('Close search') }}</span><i class="bi bi-arrow-left text-xl" aria-hidden="true"></i>
        </button>
        <div class="relative flex-1">
            <label for="overlay-search" class="sr-only">{{ __('Search products') }}</label>
            <input id="overlay-search" x-ref="input" type="search" name="q" x-model="query" autocomplete="off" spellcheck="false" enterkeyhint="search"
                   placeholder="{{ __('Search in English, বাংলা or Banglish') }}"
                   role="combobox" aria-autocomplete="list" :aria-expanded="(!!results).toString()" :aria-controls="`search-${uid}-list`"
                   :aria-activedescendant="active >= 0 ? optionId(active) : null"
                   @input="onInput($event)" @keydown="onKeydown($event)"
                   @compositionstart="composing = true" @compositionend="onCompositionEnd()"
                   class="h-11 w-full rounded-full border-0 bg-white pl-4 pr-20 text-base text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary">
            <span class="absolute right-11 top-1/2 -translate-y-1/2 text-slate-400" x-show="loading" aria-hidden="true"><i class="bi bi-arrow-repeat animate-spin"></i></span>
            <button type="button" x-show="query" @click="clear()" class="absolute right-0.5 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 sf-focus">
                <span class="sr-only">{{ __('Clear') }}</span><i class="bi bi-x-circle-fill" aria-hidden="true"></i>
            </button>
        </div>
    </form>
    <div class="flex-1 overflow-y-auto overscroll-contain bg-white pb-[env(safe-area-inset-bottom)]">
        @include('frontend.partials.search-panel', ['mode' => 'overlay'])
    </div>
</div>
