@extends('admin.layouts')
@section('title', 'Categories')
@section('ui-v2', '1')

@section('content')
@php
    $name = fn ($model, $locale = 'en') => $model->getTranslation('name', $locale, false) ?: $model->getTranslation('name', 'en');
    $iconOf = fn ($category) => $category->icon ? (str_starts_with($category->icon, 'bi-') ? $category->icon : 'bi-' . $category->icon) : 'bi-tag';
    $flattenParents = function ($nodes, string $prefix = '', int $depth = 1) use (&$flattenParents, $name) {
        $options = [];
        foreach ($nodes as $node) {
            if ($depth < \App\Models\Category::MAX_DEPTH) {
                $options[$node->id] = $prefix . $name($node);
            }
            $options += $flattenParents($node->children, $prefix . $name($node) . ' / ', $depth + 1);
        }
        return $options;
    };
    $parentOptions = $flattenParents($categories);
    $subTotal = $categories->sum(fn ($c) => $c->loadedDescendantCount());
    $admin = auth()->user();
@endphp

<div x-data="{
        category: { id: null, name: '', name_bn: '', icon: '', color: '' },
        sub: { id: null, name: '', name_bn: '', category_id: '' },
        editCategory(data) { this.category = data; this.$dispatch('open-modal', 'edit-category'); },
        editSub(data) { this.sub = data; this.$dispatch('open-modal', 'edit-subcategory'); },
     }">
    <x-admin.page-header title="Categories" :description="$categories->count() . ' categories and ' . $subTotal . ' nested categories, up to ' . \App\Models\Category::MAX_DEPTH . ' levels. For example Fashion → Cloth → Winter → Men.'" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @can('products.create')
            <div class="space-y-6">
                <x-admin.card title="Add category">
                    <form method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" value="product">
                        <x-admin.input name="name" label="Name (English)" required maxlength="255" placeholder="e.g. Smartphones" :old="false" id="new-category-name" />
                        <x-admin.input name="name_bn" label="নাম (বাংলা)" optional maxlength="255" placeholder="যেমন স্মার্টফোন" :old="false" id="new-category-name-bn"
                                       hint="Leave empty to show the English name on the Bangla site." />
                        <div class="grid grid-cols-2 gap-4">
                            <x-admin.input name="icon" label="Icon" optional placeholder="bi-phone" :old="false" id="new-category-icon"
                                           hint="Bootstrap icon class." />
                            <x-admin.input name="color" label="Colour" optional placeholder="blue-600 or #2563eb" :old="false" id="new-category-color" />
                        </div>
                        <x-admin.input name="image" type="file" label="Image" optional accept="image/jpeg,image/png,image/webp" id="new-category-image"
                                       hint="JPG, PNG or WebP up to 2 MB." />
                        <x-admin.button type="submit" icon="bi-plus-lg" class="w-full">Add category</x-admin.button>
                    </form>
                </x-admin.card>

                <x-admin.card title="Add inside a category" description="Put it under any category that is not already {{ \App\Models\Category::MAX_DEPTH }} levels deep.">
                    <form method="POST" action="{{ route('subcategories.store') }}" class="space-y-4">
                        @csrf
                        <x-admin.select name="category_id" label="Parent category" required placeholder="Select a category" :options="$parentOptions" id="new-sub-parent" />
                        <x-admin.input name="name" label="Name (English)" required maxlength="255" placeholder="e.g. Men" :old="false" id="new-sub-name" />
                        <x-admin.input name="name_bn" label="নাম (বাংলা)" optional maxlength="255" :old="false" id="new-sub-name-bn" />
                        <x-admin.button type="submit" variant="secondary" icon="bi-plus-lg" class="w-full">Add category</x-admin.button>
                    </form>
                </x-admin.card>
            </div>
        @endcan

        <div @class(['min-w-0 space-y-4', 'lg:col-span-2' => $admin->can('products.create'), 'lg:col-span-3' => !$admin->can('products.create')])>
            @forelse($categories as $category)
                @php($isHex = str_contains((string) $category->color, '#'))
                <x-admin.card :padding="false">
                    <div class="flex items-center gap-4 px-6 py-4">
                        <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-lg dark:border-gray-800 dark:bg-gray-800"
                              @if($isHex) style="color: {{ $category->color }}" @endif aria-hidden="true">
                            @if($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="" class="size-full object-cover" loading="lazy">
                            @else
                                <i class="bi {{ $iconOf($category) }} {{ !$isHex && $category->color ? 'text-' . $category->color : 'text-gray-500 dark:text-gray-400' }}"></i>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $name($category) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $category->products_count }} {{ \Illuminate\Support\Str::plural('product', $category->products_count) }} here
                                · {{ $category->loadedDescendantCount() }} nested
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @can('products.edit')
                                <x-admin.button variant="ghost" size="sm" icon="bi-pencil" title="Edit" aria-label="Edit {{ $name($category) }}"
                                                @click="editCategory({{ \Illuminate\Support\Js::from(['id' => $category->id, 'name' => $category->getTranslation('name', 'en'), 'name_bn' => $category->getTranslation('name', 'bn', false), 'icon' => $category->icon, 'color' => $category->color]) }})" />
                            @endcan
                            @can('products.delete')
                                <form method="POST" action="{{ route('categories.destroy', $category->id) }}"
                                      data-confirm="Categories that still have products or subcategories can't be deleted." data-confirm-title="Delete {{ $name($category) }}?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $name($category) }}" />
                                </form>
                            @endcan
                        </div>
                    </div>
                    @if($category->children->isNotEmpty())
                        <ul class="divide-y divide-gray-100 border-t border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            @include('admin.product.partials.category-branch', ['nodes' => $category->children, 'depth' => 1])
                        </ul>
                    @endif
                </x-admin.card>
            @empty
                <x-admin.card>
                    <x-admin.empty-state title="No categories yet" description="Add your first category to start organising products." icon="bi-diagram-3" />
                </x-admin.card>
            @endforelse
        </div>
    </div>

    @can('products.edit')
        <x-admin.modal name="edit-category" title="Edit category">
            <form id="edit-category-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  :action="@js(route('categories.update', ['category' => '__ID__'])).replace('__ID__', category.id)">
                @csrf @method('PUT')
                <x-admin.input name="name" id="edit-category-name" label="Name (English)" required maxlength="255" x-model="category.name" :old="false" />
                <x-admin.input name="name_bn" id="edit-category-name-bn" label="নাম (বাংলা)" optional maxlength="255" x-model="category.name_bn" :old="false" />
                <div class="grid grid-cols-2 gap-4">
                    <x-admin.input name="icon" id="edit-category-icon" label="Icon" optional x-model="category.icon" :old="false" />
                    <x-admin.input name="color" id="edit-category-color" label="Colour" optional x-model="category.color" :old="false" />
                </div>
                <x-admin.input name="image" type="file" id="edit-category-image" label="Replace image" optional accept="image/jpeg,image/png,image/webp"
                               hint="JPG, PNG or WebP up to 2 MB. Leave empty to keep the current image." />
            </form>
            <x-slot:footer>
                <x-admin.button variant="secondary" @click="$dispatch('close-modal', 'edit-category')">Cancel</x-admin.button>
                <x-admin.button type="submit" form="edit-category-form">Save category</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>

        <x-admin.modal name="edit-subcategory" title="Edit category">
            <form id="edit-subcategory-form" method="POST" class="space-y-4"
                  :action="@js(route('subcategories.update', ['subcategory' => '__ID__'])).replace('__ID__', sub.id)">
                @csrf @method('PUT')
                <x-admin.field label="Parent category" for="edit-sub-parent" required>
                    <select id="edit-sub-parent" name="category_id" x-model="sub.category_id" required
                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-900 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        @foreach($parentOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.input name="name" id="edit-sub-name" label="Name (English)" required maxlength="255" x-model="sub.name" :old="false" />
                <x-admin.input name="name_bn" id="edit-sub-name-bn" label="নাম (বাংলা)" optional maxlength="255" x-model="sub.name_bn" :old="false" />
            </form>
            <x-slot:footer>
                <x-admin.button variant="secondary" @click="$dispatch('close-modal', 'edit-subcategory')">Cancel</x-admin.button>
                <x-admin.button type="submit" form="edit-subcategory-form">Save category</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endcan
</div>
@endsection
