{{--
    Searchable product-variant picker backed by admin.lookup.variants.
    Use "name" for a fixed field name or "bind-name" for an Alpine expression (inside x-for rows).
    Dispatches a bubbling "variant-selected" event with the chosen item (or null when cleared).
--}}
@props([
    'name' => null,
    'bindName' => null,
    'required' => false,
    'inactive' => false,
    'requireStock' => false,
    'selected' => null,
    'placeholder' => 'Search product name, code, SKU or brand…',
])

<div x-data="variantPicker(@js(['url' => route('admin.lookup.variants'), 'inactive' => (bool) $inactive, 'requireStock' => (bool) $requireStock, 'selected' => $selected]))"
     @click.away="open = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <input type="text" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute bottom-0 left-4 h-px w-px opacity-0"
           @if($bindName) :name="{{ $bindName }}" @else name="{{ $name }}" @endif
           :value="selected ? selected.id : ''" @if($required) required @endif>

    <template x-if="selected">
        <div class="flex min-h-10 w-full items-center gap-3 rounded-lg border border-gray-300 bg-white px-3 py-1.5 shadow-xs dark:border-gray-700 dark:bg-gray-900">
            <i class="bi bi-box-seam text-brand-600 dark:text-brand-400" aria-hidden="true"></i>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-white" x-text="selected.label"></p>
                <p class="truncate text-xs" :class="selected.stock > 0 ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400'">
                    <span x-text="'Stock ' + selected.stock"></span><span x-show="selected.meta" x-text="' · ' + selected.meta"></span>
                </p>
            </div>
            <button type="button" @click="clear()" class="shrink-0 rounded text-xs font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Change</button>
        </div>
    </template>

    <template x-if="!selected">
        <div class="relative">
            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input type="text" x-ref="search" x-model.debounce.250ms="query" @focus="search()" @keydown.escape="open = false"
                   placeholder="{{ $placeholder }}" autocomplete="off" aria-label="Search product variants"
                   class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </template>

    <div x-show="open && !selected" x-cloak
         class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
        <p x-show="loading" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">Searching…</p>
        <p x-show="!loading && !results.length" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No matching variants.</p>
        <template x-for="item in results" :key="item.id">
            <button type="button" @click="choose(item)" :disabled="requireStock && item.stock <= 0"
                    class="flex w-full items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 text-left last:border-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-800 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-white" x-text="item.label"></span>
                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="item.meta"></span>
                </span>
                <span class="shrink-0 text-right">
                    <span class="block text-sm font-medium text-gray-900 tabular-nums dark:text-white" x-text="'Tk ' + Number(item.price).toLocaleString(undefined, { minimumFractionDigits: 2 })"></span>
                    <span class="block text-xs" :class="item.stock > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="item.stock > 0 ? item.stock + ' in stock' : 'Out of stock'"></span>
                </span>
            </button>
        </template>
    </div>
</div>
