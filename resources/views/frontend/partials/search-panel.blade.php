{{-- Suggestion panel body. Rendered inside an x-data="searchBox(...)" scope. --}}
@php($large = ($mode ?? 'dropdown') === 'overlay')

<div :id="`search-${uid}-list`" role="listbox" aria-label="{{ __('Search suggestions') }}" class="text-slate-800">
    {{-- Empty input: recent and trending searches --}}
    <template x-if="!results">
        <div class="space-y-5 p-4">
            <div x-show="recent.length" x-cloak>
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Recent searches') }}</p>
                    <button type="button" @click="clearRecent()" class="min-h-9 rounded px-1 text-xs font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">{{ __('Clear') }}</button>
                </div>
                <ul class="flex flex-wrap gap-2">
                    <template x-for="item in recent" :key="item">
                        <li class="flex items-center rounded-full border border-[var(--sf-border)] bg-white">
                            <button type="button" @click="submit(item)" class="flex min-h-10 items-center gap-1.5 rounded-l-full pl-3 pr-1 text-sm text-slate-700 hover:text-brand-700 sf-focus">
                                <i class="bi bi-clock-history text-slate-400" aria-hidden="true"></i><span x-text="item"></span>
                            </button>
                            <button type="button" @click="forget(item)" class="flex size-10 items-center justify-center rounded-r-full text-slate-400 hover:text-sale sf-focus" :aria-label="@js(__('Remove')) + ' ' + item">
                                <i class="bi bi-x" aria-hidden="true"></i>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
            <div x-show="trending.length">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Trending searches') }}</p>
                <ul class="flex flex-wrap gap-2">
                    <template x-for="item in trending" :key="item">
                        <li>
                            <button type="button" @click="submit(item)" class="flex min-h-10 items-center gap-1.5 rounded-full bg-slate-100 px-3 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 sf-focus">
                                <i class="bi bi-graph-up-arrow text-primary" aria-hidden="true"></i><span x-text="item"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </template>

    <template x-if="results">
        <div>
            <template x-if="results.did_you_mean">
                <p class="border-b border-[var(--sf-border)] px-4 py-2.5 text-sm text-slate-600">
                    {{ __('Did you mean') }}
                    <button type="button" @click="choose(results.did_you_mean)" class="font-semibold text-[var(--sf-accent-strong)] underline-offset-2 hover:underline sf-focus" x-text="results.did_you_mean"></button>?
                </p>
            </template>

            <template x-if="results.products.length">
                <ul class="py-1">
                    <template x-for="(product, i) in results.products" :key="product.id">
                        <li>
                            <a :href="product.url" :id="optionId(offset('product') + i)" role="option" :aria-selected="(active === offset('product') + i).toString()"
                               @click="remember(query)" @mouseenter="active = offset('product') + i"
                               class="flex items-center gap-3 px-4 {{ $large ? 'py-3' : 'py-2' }}"
                               :class="active === offset('product') + i ? 'bg-brand-50' : 'hover:bg-slate-50'">
                                <span class="sf-img relative flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white" :data-state="product.image ? 'loading' : 'error'">
                                    <i class="sf-img-fallback bi absolute text-xl text-slate-300" :class="product.icon" aria-hidden="true"></i>
                                    <template x-if="product.image">
                                        <img :src="product.image" alt="" width="48" height="48" loading="lazy" class="relative size-full object-contain p-1"
                                             x-on:load="$el.parentNode.dataset.state = 'loaded'" x-on:error="$el.parentNode.dataset.state = 'error'; $el.remove()">
                                    </template>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11px] font-semibold uppercase tracking-wider text-[var(--sf-accent-strong)]" x-text="product.brand"></span>
                                    <span class="line-clamp-1 text-sm text-slate-800" x-html="product.name_html"></span>
                                    <span class="flex items-baseline gap-2 text-sm">
                                        <span class="font-bold tabular-nums text-slate-900" x-text="product.price"></span>
                                        <span x-show="product.old_price" class="text-xs tabular-nums text-slate-500 line-through" x-text="product.old_price"></span>
                                        <span x-show="product.discount" class="rounded bg-sale px-1 text-[10px] font-bold text-white" x-text="'-' + product.discount + '%'"></span>
                                        <span x-show="!product.in_stock" class="text-xs text-slate-500">{{ __('Out of stock') }}</span>
                                    </span>
                                </span>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            <template x-if="results.categories.length || results.brands.length">
                <div class="space-y-3 border-t border-[var(--sf-border)] px-4 py-3">
                    <div x-show="results.categories.length" class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Categories') }}</span>
                        <template x-for="(category, i) in results.categories" :key="'c' + category.id">
                            <a :href="category.url" :id="optionId(offset('category') + i)" role="option" :aria-selected="(active === offset('category') + i).toString()"
                               @click="remember(query)" class="inline-flex min-h-10 items-center gap-1.5 rounded-full border px-3 text-sm"
                               :class="active === offset('category') + i ? 'border-brand-400 bg-brand-50 text-brand-800' : 'border-[var(--sf-border)] text-slate-700 hover:border-brand-300'">
                                <i class="bi" :class="category.icon" aria-hidden="true"></i><span x-html="category.name_html"></span>
                            </a>
                        </template>
                    </div>
                    <div x-show="results.brands.length" class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Brands') }}</span>
                        <template x-for="(brand, i) in results.brands" :key="'b' + brand.id">
                            <a :href="brand.url" :id="optionId(offset('brand') + i)" role="option" :aria-selected="(active === offset('brand') + i).toString()"
                               @click="remember(query)" class="inline-flex min-h-10 items-center rounded-full border px-3 text-sm font-medium"
                               :class="active === offset('brand') + i ? 'border-brand-400 bg-brand-50 text-brand-800' : 'border-[var(--sf-border)] text-slate-700 hover:border-brand-300'"
                               x-html="brand.name_html"></a>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="!results.total">
                <div class="space-y-4 px-4 py-5">
                    <p class="text-sm text-slate-600">
                        {{ __('No results for') }} “<span class="font-semibold text-slate-900" x-text="query"></span>”. {{ __('Check the spelling or try a more general word.') }}
                    </p>
                    <div x-show="results.trending.length" class="flex flex-wrap gap-2">
                        <template x-for="item in results.trending" :key="'t' + item">
                            <button type="button" @click="choose(item)" class="min-h-10 rounded-full bg-slate-100 px-3 text-sm text-slate-700 hover:bg-brand-50 sf-focus" x-text="item"></button>
                        </template>
                    </div>
                    <div x-show="results.popular.length">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Popular right now') }}</p>
                        <ul class="divide-y divide-[var(--sf-border)]">
                            <template x-for="product in results.popular" :key="'p' + product.id">
                                <li>
                                    <a :href="product.url" class="flex items-center justify-between gap-3 py-2 text-sm hover:text-brand-700">
                                        <span class="line-clamp-1" x-text="product.name"></span>
                                        <span class="shrink-0 font-semibold tabular-nums" x-text="product.price"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>

            <a :href="results.see_all_url" :id="optionId(offset('all'))" role="option" :aria-selected="(active === offset('all')).toString()"
               @click="remember(query)" @mouseenter="active = offset('all')"
               class="flex min-h-12 items-center justify-between gap-2 border-t border-[var(--sf-border)] px-4 text-sm font-semibold text-[var(--sf-accent-strong)]"
               :class="active === offset('all') ? 'bg-brand-50' : 'hover:bg-slate-50'">
                <span>{{ __('See all results for') }} “<span x-text="query"></span>”<span x-show="results.total" x-text="' (' + results.total + ')'"></span></span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </template>
</div>
