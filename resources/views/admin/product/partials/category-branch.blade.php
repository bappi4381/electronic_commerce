@foreach($nodes as $node)
    <li>
        <div class="flex items-center gap-3 py-2.5 pr-6" style="padding-left: {{ 1.5 + $depth * 1.25 }}rem">
            <i class="bi bi-arrow-return-right text-xs text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
            <span class="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-300">
                {{ $name($node) }}
                <span class="text-xs text-gray-400 dark:text-gray-500">· {{ $node->products_count }} {{ \Illuminate\Support\Str::plural('product', $node->products_count) }}</span>
            </span>
            <div class="flex shrink-0 items-center gap-1">
                @can('products.edit')
                    <x-admin.button variant="ghost" size="sm" icon="bi-pencil" title="Edit" aria-label="Edit {{ $name($node) }}"
                                    @click="editSub({{ \Illuminate\Support\Js::from(['id' => $node->id, 'name' => $node->getTranslation('name', 'en'), 'name_bn' => $node->getTranslation('name', 'bn', false), 'category_id' => (string) $node->parent_id]) }})" />
                @endcan
                @can('products.delete')
                    <form method="POST" action="{{ route('subcategories.destroy', $node->id) }}"
                          data-confirm="Categories that still have products or subcategories can't be deleted." data-confirm-title="Delete {{ $name($node) }}?" data-confirm-button="Delete">
                        @csrf @method('DELETE')
                        <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $name($node) }}" />
                    </form>
                @endcan
            </div>
        </div>
        @if($node->children->isNotEmpty())
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                @include('admin.product.partials.category-branch', ['nodes' => $node->children, 'depth' => $depth + 1])
            </ul>
        @endif
    </li>
@endforeach
