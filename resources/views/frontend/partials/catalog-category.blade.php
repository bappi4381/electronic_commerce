@foreach($nodes as $node)
    @continue((int) $node->total_count < 1)
    @php($hasChildren = $node->children->contains(fn ($child) => $child->total_count > 0))
    @php($expanded = $hasChildren && $activeCategory && in_array($activeCategory->id, $node->loadedIds(), true))
    <div x-data="{ open: @js((bool) $expanded) }">
        <div class="flex items-center">
            <label class="{{ $option }} min-w-0 flex-1" style="padding-left: {{ $depth ? 0.5 + $depth * 0.75 : 0.5 }}rem">
                <input type="radio" name="category" value="{{ $node->id }}" @checked($activeCategory?->id === $node->id) class="size-4 shrink-0 accent-brand-600">
                <span class="flex-1 truncate">{{ $node->name }}</span>
                <span class="text-xs font-normal tabular-nums text-slate-400">{{ localized_number($node->total_count) }}</span>
            </label>
            @if($hasChildren)
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                        class="flex size-10 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-50 hover:text-slate-700 sf-focus">
                    <span class="sr-only">{{ __('Subcategories of :category', ['category' => $node->name]) }}</span>
                    <i class="bi bi-chevron-down text-xs transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
                </button>
            @else
                <span class="size-10 shrink-0" aria-hidden="true"></span>
            @endif
        </div>
        @if($hasChildren)
            <div x-show="open" @unless($expanded) style="display: none" @endunless class="mb-1 ml-4 border-l border-[var(--sf-border)]">
                @include('frontend.partials.catalog-category', ['nodes' => $node->children, 'depth' => $depth + 1])
            </div>
        @endif
    </div>
@endforeach
