@foreach($nodes as $node)
    <option value="{{ $node->id }}" @selected((int) ($selected ?? 0) === $node->id)>{{ str_repeat('— ', $depth) }}{{ $node->getTranslation('name', 'en') }}</option>
    @if($node->children->isNotEmpty())
        @include('admin.product.partials.category-options', ['nodes' => $node->children, 'depth' => $depth + 1, 'selected' => $selected ?? 0])
    @endif
@endforeach
