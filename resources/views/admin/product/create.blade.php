@extends('admin.layouts')
@section('title', isset($product) ? 'Edit product' : 'Add product')
@section('ui-v2', '1')

@section('content')
@php
    $editing = isset($product);
    $siteName = \App\Models\Setting::get('site_name', 'ONEMALL');

    $variantRows = old('variants') !== null
        ? collect(old('variants'))->values()->map(fn ($v) => [
            'id' => $v['id'] ?? null,
            'sku' => $v['sku'] ?? '',
            'price' => $v['price'] ?? '',
            'stock' => $v['stock'] ?? 0,
            'original_stock' => $v['original_stock'] ?? null,
            'attribute_value_ids' => array_map('intval', $v['attribute_value_ids'] ?? []),
        ])
        : ($editing ? $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku ?? '',
            'price' => $v->price ?? '',
            'stock' => (int) $v->stock,
            'original_stock' => (int) $v->stock,
            'attribute_value_ids' => $v->attributeValues->pluck('id')->all(),
        ]) : collect());

    $tabOf = fn (string $key) => match (true) {
        str_starts_with($key, 'variants') => 'variants',
        in_array($key, ['price', 'discount', 'low_stock_threshold', 'is_flash_deal'], true) => 'pricing',
        str_starts_with($key, 'images') || $key === 'video_link' => 'images',
        default => 'general',
    };
    $errorTabs = collect($errors->keys())->mapWithKeys(fn ($key) => [$tabOf($key) => true])->all();
    $variantErrors = collect($errors->getMessages())->filter(fn ($m, $key) => str_starts_with($key, 'variants.'))->map(fn ($m) => $m[0]);

    $config = [
        'tab' => $errors->any() ? $tabOf($errors->keys()[0]) : 'general',
        'attributes' => $attributePayload['attributes'],
        'mappings' => $attributePayload['categoryAttributeMappings'],
        'categoryId' => (int) old('category_id', $product->category_id ?? 0),
        'nameEn' => (string) old('name_en', $editing ? $product->getTranslation('name', 'en') : ''),
        'descriptionEn' => (string) old('description_en', $editing ? $product->getTranslation('description', 'en') : ''),
        'price' => old('price', $product->price ?? ''),
        'discount' => old('discount', isset($product) ? $product->regularDiscount() : 0),
        'variants' => $variantRows->values(),
        'variantErrors' => $variantErrors,
        'image' => $editing && $product->images->count() ? Storage::url($product->images->first()->image) : null,
        'maxImageKb' => 2048,
    ];

    $tabs = [
        'general' => ['label' => 'General', 'icon' => 'bi-card-text'],
        'pricing' => ['label' => 'Pricing', 'icon' => 'bi-tag'],
        'variants' => ['label' => 'Variants', 'icon' => 'bi-boxes'],
        'images' => ['label' => 'Images', 'icon' => 'bi-images'],
        'seo' => ['label' => 'SEO', 'icon' => 'bi-search'],
    ];
@endphp

<div x-data="productForm({{ \Illuminate\Support\Js::from($config) }})">
    <x-admin.page-header :title="$editing ? $product->getTranslation('name', 'en') : 'Add product'"
                         :description="$editing ? 'Product code ' . $product->product_id : 'Create a product, its variants and stock.'"
                         :breadcrumbs="['Products' => route('admin.products.index'), ($editing ? 'Edit' : 'Add product') => null]">
        @if($editing)
            <x-admin.button variant="ghost" :href="route('products.show', ['locale' => 'en', 'id' => $product->id])" target="_blank" rel="noopener" icon="bi-box-arrow-up-right">View in store</x-admin.button>
        @endif
        <x-admin.button variant="secondary" :href="route('admin.products.index')" class="max-lg:hidden">Cancel</x-admin.button>
        <x-admin.button type="submit" form="productForm" icon="bi-check-lg" class="max-lg:hidden">{{ $editing ? 'Save changes' : 'Create product' }}</x-admin.button>
    </x-admin.page-header>

    <form id="productForm" method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          @invalid.capture="onInvalid($event)">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <x-admin.card :padding="false">
                    <x-admin.tabs :tabs="$tabs" :errors="$errorTabs" label="Product sections" class="px-4" />

                    <div class="p-6">
                        {{-- General --}}
                        <x-admin.tab-panel name="general" class="space-y-6">
                            <x-admin.field label="Category" for="category" error="category_id" required
                                           hint="Determines which attributes (size, colour, …) the variants can use.">
                                <select id="category" name="category_id" required x-model.number="categoryId"
                                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-900 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">Select a category</option>
                                    @include('admin.product.partials.category-options', ['nodes' => $categories, 'depth' => 0, 'selected' => $config['categoryId']])
                                </select>
                            </x-admin.field>

                            <div x-data="{ lang: 'en' }" class="rounded-lg border border-gray-200 dark:border-gray-800">
                                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-2 dark:border-gray-800">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Name & description</p>
                                    <div class="inline-flex rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800" role="group" aria-label="Language">
                                        @foreach(['en' => 'English', 'bn' => 'বাংলা'] as $code => $language)
                                            <button type="button" @click="lang = '{{ $code }}'" :aria-pressed="(lang === '{{ $code }}').toString()"
                                                    class="rounded-md px-3 py-1 text-xs font-medium focus-ring"
                                                    :class="lang === '{{ $code }}' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ $language }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="space-y-4 p-4" x-show="lang === 'en'">
                                    <x-admin.input name="name_en" label="Product name" required maxlength="255" x-model="nameEn"
                                                   :value="$config['nameEn']" placeholder="e.g. Galaxy S24 Ultra 256 GB" />
                                    <x-admin.textarea name="description_en" label="Description" rows="6" x-model="descriptionEn"
                                                      :value="$config['descriptionEn']" placeholder="Key features, what's in the box, specifications…" />
                                </div>
                                <div class="space-y-4 p-4" x-show="lang === 'bn'" x-cloak>
                                    <x-admin.input name="name_bn" label="পণ্যের নাম" optional maxlength="255" :value="$editing ? $product->getTranslation('name', 'bn') : ''" />
                                    <x-admin.textarea name="description_bn" label="বিবরণ" optional rows="6" :value="$editing ? $product->getTranslation('description', 'bn') : ''" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <x-admin.select name="brand_id" label="Brand" optional placeholder="No brand" :value="$product->brand_id ?? null"
                                                    :options="$brands->mapWithKeys(fn ($b) => [$b->id => $b->name . ($b->status ? '' : ' (inactive)')])->all()" />
                                    @can('products.create')
                                        <a href="{{ route('admin.brands.index') }}" target="_blank" class="mt-1.5 inline-block rounded text-xs font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Manage brands</a>
                                    @endcan
                                </div>
                                <x-admin.input name="model" label="Model" optional maxlength="100" :value="$product->model ?? null" placeholder="e.g. SM-S928B" />
                                <x-admin.input name="warranty_period" label="Warranty" optional :value="$product->warranty_period ?? null" placeholder="e.g. 1 year" />
                            </div>
                        </x-admin.tab-panel>

                        {{-- Pricing --}}
                        <x-admin.tab-panel name="pricing" class="space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <x-admin.input name="price" type="number" label="Base price" required min="0" step="0.01" prefix="Tk"
                                               x-model="price" :value="$config['price']" placeholder="0.00" />
                                <x-admin.input name="discount" type="number" label="Discount" min="0" max="100" step="0.01" suffix="%"
                                               x-model="discount" :value="$config['discount']" hint="Also applies to variants with their own price." />
                                <x-admin.field label="Sale price" hint="Calculated from price and discount.">
                                    <div class="flex h-10 items-center rounded-lg bg-gray-50 px-3 text-sm font-semibold text-gray-900 tabular-nums dark:bg-gray-800 dark:text-white" x-text="money(salePrice)" aria-live="polite"></div>
                                </x-admin.field>
                            </div>
                            <x-admin.input name="low_stock_threshold" type="number" label="Low-stock alert threshold" min="0" class="sm:max-w-48"
                                           :value="$product->low_stock_threshold ?? 5" hint="You'll be notified when a variant's stock falls to this number." />
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                                <x-admin.toggle name="is_flash_deal" label="Feature in Flash Deals" :checked="$product->is_flash_deal ?? false"
                                                description="Shows the product in the storefront's flash-deal section while it has a discount." />
                            </div>
                        </x-admin.tab-panel>

                        {{-- Variants --}}
                        <x-admin.tab-panel name="variants" class="space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Variants & stock</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Each variant has its own SKU, optional price and stock. Stock edits are recorded as movements.</p>
                                </div>
                                <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" @click="addVariant()">Add variant</x-admin.button>
                            </div>

                            <template x-if="applicableAttributes.length">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Attributes for this category:
                                    <span class="font-medium text-gray-700 dark:text-gray-300" x-text="applicableAttributes.map(a => a.name).join(', ')"></span>
                                </p>
                            </template>

                            <p x-show="!applicableAttributes.length" class="text-xs text-gray-500 dark:text-gray-400">
                                No attributes yet. <a href="{{ route('admin.attributes.index') }}" target="_blank" class="font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Create attributes</a>
                                such as Color and Size to generate variants automatically.
                            </p>

                            <div x-show="applicableAttributes.length" class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Quick generate</h4>
                                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Pick the options you sell, e.g. Black, Red, Blue and 40, 41, 42. One variant is created for every combination; existing combinations are skipped.</p>
                                <div class="space-y-3">
                                    <template x-for="attribute in applicableAttributes" :key="attribute.id">
                                        <div>
                                            <p class="mb-1.5 text-xs font-medium text-gray-700 dark:text-gray-300" x-text="attribute.name"></p>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="value in attribute.values" :key="value.id">
                                                    <button type="button" @click="toggleGeneratorValue(attribute.id, value.id)"
                                                            :aria-pressed="isGeneratorValue(attribute.id, value.id).toString()"
                                                            class="inline-flex select-none items-center rounded-md border px-2.5 py-1 text-xs font-medium transition-colors focus-ring"
                                                            :class="isGeneratorValue(attribute.id, value.id) ? 'border-brand-600 bg-brand-600 text-white dark:border-brand-500 dark:bg-brand-500 dark:text-gray-950' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                                                            x-text="value.value"></button>
                                                </template>
                                                <span x-show="!attribute.values.length" class="text-xs text-gray-500 dark:text-gray-400">No values yet.</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-4 flex flex-wrap items-end gap-3">
                                    <div class="space-y-1.5">
                                        <label for="generator-stock" class="text-xs font-medium text-gray-700 dark:text-gray-300">Stock for each</label>
                                        <input id="generator-stock" type="number" min="0" step="1" placeholder="0" x-model="generator.stock" @keydown.enter.prevent="generateVariants()"
                                               class="block h-8 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    </div>
                                    <x-admin.button size="sm" icon="bi-magic" x-bind:disabled="!generatorCount" @click="generateVariants()">
                                        <span x-text="generatorCount ? `Generate ${generatorCount} ${generatorCount === 1 ? 'variant' : 'variants'}` : 'Generate variants'"></span>
                                    </x-admin.button>
                                </div>
                            </div>

                            <template x-for="(variant, index) in variants" :key="variant.key">
                                <fieldset class="rounded-lg border border-gray-200 dark:border-gray-800">
                                    <legend class="sr-only" x-text="'Variant ' + (index + 1)"></legend>
                                    <div class="flex items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-800 dark:bg-gray-800/40">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <span x-text="'Variant ' + (index + 1)"></span>
                                            <span x-show="variantLabel(variant)" class="font-normal text-gray-500 dark:text-gray-400" x-text="'· ' + variantLabel(variant)"></span>
                                        </p>
                                        <x-admin.button variant="ghost" size="sm" icon="bi-trash" class="text-red-600 dark:text-red-400" @click="removeVariant(index)">Remove</x-admin.button>
                                    </div>
                                    <div class="space-y-4 p-4">
                                        <template x-if="variant.id">
                                            <div>
                                                <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id">
                                                <input type="hidden" :name="`variants[${index}][original_stock]`" :value="variant.original_stock">
                                            </div>
                                        </template>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            @foreach(['sku' => ['SKU', 'text', 'e.g. S24U-256-BLK', ''], 'price' => ['Price override', 'number', 'Uses base price', 'Tk'], 'stock' => ['Stock', 'number', '0', '']] as $field => [$fieldLabel, $fieldType, $placeholder, $prefix])
                                                <div class="space-y-1.5">
                                                    <label :for="`variant-${index}-{{ $field }}`" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $fieldLabel }}@if($field === 'stock')<span class="text-red-600" aria-hidden="true"> *</span>@endif
                                                    </label>
                                                    <div class="relative">
                                                        @if($prefix)<span class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-sm text-gray-500">{{ $prefix }}</span>@endif
                                                        <input type="{{ $fieldType }}" :id="`variant-${index}-{{ $field }}`" :name="`variants[${index}][{{ $field }}]`" x-model="variant.{{ $field }}"
                                                               placeholder="{{ $placeholder }}" @if($fieldType === 'number') min="0" @endif @if($field === 'price') step="0.01" @endif @if($field === 'stock') required step="1" @endif
                                                               :aria-invalid="variantError(index, '{{ $field }}') ? 'true' : null"
                                                               class="block h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-gray-100 {{ $prefix ? 'pl-10' : '' }}"
                                                               :class="variantError(index, '{{ $field }}') ? 'border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500/30 dark:border-gray-700'">
                                                    </div>
                                                    <p x-show="variantError(index, '{{ $field }}')" class="text-xs font-medium text-red-600 dark:text-red-400" x-text="variantError(index, '{{ $field }}')"></p>
                                                    @if($field === 'stock')
                                                        <p x-show="variant.id && Number(variant.stock) !== Number(variant.original_stock)" class="text-xs text-gray-500 dark:text-gray-400"
                                                           x-text="'Will record a ' + (variant.stock - variant.original_stock > 0 ? '+' : '') + (variant.stock - variant.original_stock) + ' stock adjustment'"></p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <template x-for="attribute in applicableAttributes" :key="attribute.id">
                                            <div>
                                                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300" x-text="attribute.name"></p>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="value in attribute.values" :key="value.id">
                                                        <label class="cursor-pointer">
                                                            <input type="checkbox" class="peer sr-only" :name="`variants[${index}][attribute_value_ids][]`" :value="value.id"
                                                                   :checked="variant.attribute_value_ids.includes(value.id)" @change="toggleValue(variant, value.id)">
                                                            <span class="inline-flex select-none items-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 transition-colors peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:peer-checked:border-brand-500 dark:peer-checked:bg-brand-500 dark:peer-checked:text-gray-950"
                                                                  x-text="value.value"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <p x-show="variantError(index, 'attribute_value_ids')" class="text-xs font-medium text-red-600 dark:text-red-400" x-text="variantError(index, 'attribute_value_ids')"></p>
                                    </div>
                                </fieldset>
                            </template>

                            <div x-show="!variants.length" class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                                <x-admin.empty-state title="No variants yet" icon="bi-boxes"
                                                     description="Products without variants are not stock-tracked. Add at least one variant to sell with stock control.">
                                    <x-admin.button variant="secondary" icon="bi-plus-lg" @click="addVariant()">Add first variant</x-admin.button>
                                </x-admin.empty-state>
                            </div>
                        </x-admin.tab-panel>

                        {{-- Images --}}
                        <x-admin.tab-panel name="images" class="space-y-6">
                            @if($editing && $product->images->count())
                                <div>
                                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Current images</h3>
                                    <ul class="grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-5">
                                        @foreach($product->images as $image)
                                            <li class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                                                <img src="{{ Storage::url($image->image) }}" alt="Product image {{ $loop->iteration }}" class="size-full object-cover" loading="lazy">
                                                @if($loop->first)<span class="absolute left-2 top-2 rounded bg-gray-900/80 px-1.5 py-0.5 text-[10px] font-medium text-white">Cover</span>@endif
                                                <button type="button" @click="document.getElementById('delete-img-{{ $image->id }}').requestSubmit()"
                                                        class="absolute right-2 top-2 inline-flex size-8 items-center justify-center rounded-lg bg-white/90 text-red-600 shadow-sm opacity-100 transition-opacity focus-ring sm:opacity-0 sm:group-hover:opacity-100 sm:focus:opacity-100">
                                                    <span class="sr-only">Remove image {{ $loop->iteration }}</span><i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <x-admin.field label="{{ $editing ? 'Add more images' : 'Images' }}" error="images" hint="JPG, PNG or WEBP, up to 2 MB each. The first image is used as the cover.">
                                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
                                       class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-10 text-center transition-colors focus-within:ring-2 focus-within:ring-brand-500"
                                       :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-300 hover:border-gray-400 dark:border-gray-700'">
                                    <i class="bi bi-cloud-arrow-up text-3xl text-gray-400" aria-hidden="true"></i>
                                    <span class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Drop images here or <span class="text-brand-600 dark:text-brand-400">browse</span></span>
                                    <input type="file" name="images[]" x-ref="images" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="sr-only"
                                           @change="addFiles($event.target.files)">
                                </label>
                            </x-admin.field>
                            @foreach($errors->get('images.*') as $messages)
                                <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $messages[0] }}</p>
                            @endforeach

                            <ul x-show="files.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4 xl:grid-cols-5">
                                <template x-for="(item, index) in files" :key="item.url">
                                    <li class="relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
                                        <img :src="item.url" :alt="item.file.name" class="size-full object-cover">
                                        <span class="absolute inset-x-0 bottom-0 truncate bg-gray-900/70 px-2 py-1 text-[10px] text-white" x-text="item.file.name"></span>
                                        <button type="button" @click="removeFile(index)" class="absolute right-2 top-2 inline-flex size-7 items-center justify-center rounded-lg bg-white/90 text-gray-700 shadow-sm focus-ring">
                                            <span class="sr-only" x-text="'Remove ' + item.file.name"></span><i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                                        </button>
                                    </li>
                                </template>
                            </ul>

                            <x-admin.input name="video_link" type="url" label="Video link" optional prefix="bi-play-circle" :value="$product->video_link ?? null"
                                           placeholder="https://youtube.com/watch?v=…" hint="Shown on the product page." />
                        </x-admin.tab-panel>

                        {{-- SEO --}}
                        <x-admin.tab-panel name="seo" class="space-y-6">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Search engine preview</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">How this product is likely to appear in search results, based on its English name and description.</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ preg_replace('#^https?://#', '', url('/en/product')) }}/{{ $editing ? $product->id : '…' }}</p>
                                <p class="mt-1 truncate text-lg text-[#1a0dab] dark:text-[#8ab4f8]" x-text="seoTitle"></p>
                                <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400" x-text="seoDescription"></p>
                            </div>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Title length</dt>
                                    <dd class="mt-1 text-sm font-semibold tabular-nums" :class="seoTitle.length > 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white'"
                                        x-text="seoTitle.length + ' / 60 characters'"></dd>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Description length</dt>
                                    <dd class="mt-1 text-sm font-semibold tabular-nums" :class="plainDescription.length < 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white'"
                                        x-text="Math.min(plainDescription.length, 160) + ' / 160 characters used'"></dd>
                                </div>
                            </dl>
                            <x-admin.alert tone="info">
                                Products don't have separate meta title, meta description or URL fields yet, so the storefront uses the product name and the start of the description. Write a clear name and a first sentence that summarises the product.
                            </x-admin.alert>
                        </x-admin.tab-panel>
                    </div>
                </x-admin.card>
            </div>

            {{-- Summary --}}
            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="Product summary">
                <x-admin.card :padding="false">
                    <div class="aspect-[4/3] overflow-hidden rounded-t-xl bg-gray-100 dark:bg-gray-800">
                        <template x-if="coverImage">
                            <img :src="coverImage" alt="" class="size-full object-contain">
                        </template>
                        <div x-show="!coverImage" class="flex size-full items-center justify-center text-4xl text-gray-300 dark:text-gray-600"><i class="bi bi-image" aria-hidden="true"></i></div>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <p class="truncate text-base font-semibold text-gray-900 dark:text-white" x-text="nameEn || 'Untitled product'"></p>
                            <p class="mt-1 flex items-baseline gap-2">
                                <span class="text-lg font-semibold text-gray-900 tabular-nums dark:text-white" x-text="money(salePrice)"></span>
                                <span x-show="Number(discount) > 0" class="text-sm text-gray-400 line-through tabular-nums" x-text="money(price)"></span>
                                <span x-show="Number(discount) > 0" class="rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400" x-text="'-' + Number(discount) + '%'"></span>
                            </p>
                        </div>
                        <dl class="divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Variants</dt><dd class="font-medium tabular-nums" x-text="variants.length"></dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Total stock</dt><dd class="font-medium tabular-nums" x-text="totalStock"></dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Images</dt><dd class="font-medium tabular-nums" x-text="{{ $editing ? $product->images->count() : 0 }} + files.length"></dd></div>
                            @if($editing)
                                <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Rating</dt><dd class="font-medium">{{ $product->rating_count ? number_format($product->rating_avg, 1) . ' ★ (' . $product->rating_count . ')' : 'No reviews' }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </x-admin.card>
            </aside>
        </div>

        {{-- Mobile save bar --}}
        <div class="sticky bottom-0 z-20 -mx-4 mt-6 flex gap-2 border-t border-gray-200 bg-white/95 p-4 backdrop-blur sm:-mx-6 lg:hidden dark:border-gray-800 dark:bg-gray-900/95">
            <x-admin.button variant="secondary" :href="route('admin.products.index')" class="flex-1">Cancel</x-admin.button>
            <x-admin.button type="submit" class="flex-1">{{ $editing ? 'Save' : 'Create' }}</x-admin.button>
        </div>
    </form>
</div>

@if($editing)
    @foreach($product->images as $image)
        <form id="delete-img-{{ $image->id }}" action="{{ route('admin.products.image.destroy', $image) }}" method="POST" class="hidden"
              data-confirm="The image is deleted immediately. Unsaved changes on this page will be lost." data-confirm-title="Remove this image?" data-confirm-button="Remove image">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
    function productForm(config) {
        let nextKey = 1;
        const withKey = (variant) => ({ key: nextKey++, ...variant, attribute_value_ids: (variant.attribute_value_ids || []).map(Number) });

        return {
            tab: config.tab,
            categoryId: config.categoryId || '',
            nameEn: config.nameEn,
            descriptionEn: config.descriptionEn,
            price: config.price,
            discount: config.discount,
            variants: config.variants.map(withKey),
            files: [],
            dragging: false,
            generator: { selected: {}, stock: '' },

            get applicableAttributes() {
                const mapping = config.mappings.find((m) => m.id === Number(this.categoryId));
                return mapping?.attribute_ids.length ? config.attributes.filter((a) => mapping.attribute_ids.includes(a.id)) : config.attributes;
            },
            get generatorGroups() {
                return this.applicableAttributes
                    .map((a) => a.values.map((v) => v.id).filter((id) => (this.generator.selected[a.id] || []).includes(id)))
                    .filter((ids) => ids.length);
            },
            get generatorCount() {
                const groups = this.generatorGroups;
                return groups.length ? groups.reduce((count, ids) => count * ids.length, 1) : 0;
            },
            get salePrice() {
                const price = Number(this.price) || 0;
                return price - price * ((Number(this.discount) || 0) / 100);
            },
            get totalStock() {
                return this.variants.reduce((sum, v) => sum + (Number(v.stock) || 0), 0);
            },
            get coverImage() {
                return config.image || this.files[0]?.url || null;
            },
            get plainDescription() {
                return this.descriptionEn.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            },
            get seoTitle() {
                return (this.nameEn || 'Product name') + ' | ' + @js($siteName);
            },
            get seoDescription() {
                const text = this.plainDescription;
                return text ? (text.length > 160 ? text.slice(0, 157) + '…' : text) : 'Add a description to control the snippet shown in search results.';
            },

            money(value) {
                return 'Tk ' + (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            variantError(index, field) {
                return config.variantErrors[`variants.${index}.${field}`] || null;
            },
            variantLabel(variant) {
                return config.attributes.flatMap((a) => a.values).filter((v) => variant.attribute_value_ids.includes(v.id)).map((v) => v.value).join(' / ');
            },
            addVariant() {
                this.variants.push(withKey({ id: null, sku: '', price: '', stock: 0, original_stock: null, attribute_value_ids: [] }));
                this.tab = 'variants';
                this.$nextTick(() => document.getElementById(`variant-${this.variants.length - 1}-sku`)?.focus());
            },
            async removeVariant(index) {
                const variant = this.variants[index];
                if (variant.id) {
                    const ok = await confirmAction({
                        title: 'Remove this variant?',
                        message: 'When you save, it is deleted — or deactivated if it already has orders or stock history.',
                        confirmText: 'Remove variant',
                    });
                    if (!ok) return;
                }
                this.variants.splice(index, 1);
            },
            toggleValue(variant, id) {
                variant.attribute_value_ids = variant.attribute_value_ids.includes(id)
                    ? variant.attribute_value_ids.filter((v) => v !== id)
                    : [...variant.attribute_value_ids, id];
            },
            isGeneratorValue(attributeId, id) {
                return (this.generator.selected[attributeId] || []).includes(id);
            },
            toggleGeneratorValue(attributeId, id) {
                const current = this.generator.selected[attributeId] || [];
                this.generator.selected = {
                    ...this.generator.selected,
                    [attributeId]: current.includes(id) ? current.filter((v) => v !== id) : [...current, id],
                };
            },
            generateVariants() {
                if (!this.generatorCount) return;

                const combos = this.generatorGroups.reduce((acc, ids) => acc.flatMap((combo) => ids.map((id) => [...combo, id])), [[]]);
                const keyOf = (ids) => [...ids].sort((a, b) => a - b).join(',');
                const isBlank = (v) => !v.id && !v.attribute_value_ids.length && !v.sku && v.price === '' && !Number(v.stock);
                this.variants = this.variants.filter((v) => !isBlank(v));

                const taken = new Set(this.variants.map((v) => keyOf(v.attribute_value_ids)));
                const stock = Math.max(0, parseInt(this.generator.stock, 10) || 0);
                let added = 0;

                for (const ids of combos) {
                    if (taken.has(keyOf(ids))) continue;
                    taken.add(keyOf(ids));
                    this.variants.push(withKey({ id: null, sku: '', price: '', stock, original_stock: null, attribute_value_ids: ids }));
                    added++;
                }

                const skipped = combos.length - added;
                toast(added
                    ? `${added} ${added === 1 ? 'variant' : 'variants'} added${skipped ? `, ${skipped} already existed` : ''}. Set each one's stock below.`
                    : 'All of these combinations already exist.', added ? 'success' : 'error');
            },
            addFiles(list) {
                for (const file of list) {
                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                        toast(`${file.name} is not a JPG, PNG or WEBP image.`, 'error');
                        continue;
                    }
                    if (file.size > config.maxImageKb * 1024) {
                        toast(`${file.name} is larger than 2 MB.`, 'error');
                        continue;
                    }
                    this.files.push({ file, url: URL.createObjectURL(file) });
                }
                this.syncFiles();
            },
            removeFile(index) {
                URL.revokeObjectURL(this.files[index].url);
                this.files.splice(index, 1);
                this.syncFiles();
            },
            syncFiles() {
                const transfer = new DataTransfer();
                this.files.forEach((item) => transfer.items.add(item.file));
                this.$refs.images.files = transfer.files;
            },
            onInvalid(event) {
                const panel = event.target.closest('[data-tab]');
                if (panel && this.tab !== panel.dataset.tab) {
                    this.tab = panel.dataset.tab;
                    this.$nextTick(() => event.target.reportValidity());
                }
            },
        };
    }
</script>
@endpush
