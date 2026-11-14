@foreach($nodes as $node)
    @php($count = $countOf($node))
    @continue($categoryCounts && $count < 1)
    <label class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded py-0 pr-1 text-sm text-slate-700 hover:bg-slate-50" style="padding-left: {{ 0.25 + $depth * 1 }}rem">
        <input type="radio" name="category" value="{{ $node->id }}" @checked((int) $activeCategory === $node->id) class="size-4 border-slate-300 text-primary focus:ring-primary/40">
        <span class="flex-1 truncate">{{ $node->name }}</span>
        @if($count)<span class="text-xs tabular-nums text-slate-400">{{ localized_number($count) }}</span>@endif
    </label>
    @if($node->children->isNotEmpty())
        @include('frontend.partials.search-category', ['nodes' => $node->children, 'depth' => $depth + 1])
    @endif
@endforeach
