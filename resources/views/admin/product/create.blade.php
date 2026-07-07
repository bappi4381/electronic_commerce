@extends('admin.layouts')
@section('title', isset($product) ? 'Edit Product' : 'Add New Product')

@push('styles')
<style>
    .variant-row { animation: fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .tab-btn.active { background: #3b82f6; color: #fff; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                {{ isset($product) ? 'Edit Product' : 'Add New Product' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Fill in both English & Bengali fields for full multi-language support.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="flex items-center gap-2 text-sm font-bold text-slate-600 bg-white border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    @php
        $selectedCategoryAttributes = isset($selectedCategoryAttributes) && $selectedCategoryAttributes->isNotEmpty()
            ? $selectedCategoryAttributes
            : $attributes;
    @endphp

    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}"
        method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        @if(isset($product)) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Column: Main Info --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Language Tabs for Name & Description --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="flex border-b border-slate-100 bg-slate-50">
                        <button type="button" onclick="switchTab('en')" id="tab-en"
                            class="tab-btn active px-6 py-3.5 text-sm font-bold rounded-none transition-all flex items-center gap-2">
                            🇬🇧 English
                        </button>
                        <button type="button" onclick="switchTab('bn')" id="tab-bn"
                            class="tab-btn px-6 py-3.5 text-sm font-bold rounded-none text-slate-500 transition-all flex items-center gap-2 hover:bg-slate-100">
                            🇧🇩 বাংলা
                        </button>
                    </div>

                    {{-- English --}}
                    <div id="content-en" class="tab-content active p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Product Name (English) <span class="text-red-500">*</span></label>
                            <input type="text" name="name_en" value="{{ old('name_en', isset($product) ? $product->getTranslation('name','en') : '') }}"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all"
                                placeholder="e.g. Premium Cotton T-Shirt" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Description (English)</label>
                            <textarea name="description_en" rows="5"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all"
                                placeholder="Write a detailed product description in English...">{{ old('description_en', isset($product) ? $product->getTranslation('description','en') : '') }}</textarea>
                        </div>
                    </div>

                    {{-- Bangla --}}
                    <div id="content-bn" class="tab-content p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">পণ্যের নাম (বাংলা)</label>
                            <input type="text" name="name_bn" value="{{ old('name_bn', isset($product) ? $product->getTranslation('name','bn') : '') }}"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all"
                                placeholder="যেমন: প্রিমিয়াম কটন টি-শার্ট">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">বিবরণ (বাংলা)</label>
                            <textarea name="description_bn" rows="5"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all"
                                placeholder="বাংলায় পণ্যের বিস্তারিত বিবরণ লিখুন...">{{ old('description_bn', isset($product) ? $product->getTranslation('description','bn') : '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Product Variants Manager --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-black text-slate-800">Product Variants</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Each variant has its own Stock, Price & SKU. e.g. Red-M, Blue-L</p>
                        </div>
                        <button type="button" onclick="addVariant()"
                            class="flex items-center gap-2 text-sm font-bold bg-primary text-white px-4 py-2 rounded-xl hover:bg-primary-dark transition-all">
                            <i class="bi bi-plus-lg"></i> Add Variant
                        </button>
                    </div>

                    <div id="variants-container" class="divide-y divide-slate-100">
                        @if(isset($product) && $product->variants->count())
                            @foreach($product->variants as $vi => $variant)
                            <div class="variant-row p-5 space-y-3" id="variant-{{ $vi }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black uppercase text-slate-500 tracking-widest">Variant #{{ $vi + 1 }}</span>
                                    <button type="button" onclick="removeVariant({{ $vi }})"
                                        class="text-red-400 hover:text-red-600 transition-colors text-sm font-bold">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">SKU</label>
                                        <input type="text" name="variants[{{ $vi }}][sku]" value="{{ $variant->sku }}"
                                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                                            placeholder="e.g. TSH-RED-M">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Price Override (৳)</label>
                                        <input type="number" name="variants[{{ $vi }}][price]" value="{{ $variant->price }}" min="0" step="0.01"
                                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                                            placeholder="Leave blank = base price">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Stock <span class="text-red-500">*</span></label>
                                        <input type="number" name="variants[{{ $vi }}][stock]" value="{{ $variant->stock }}" min="0" required
                                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                                            placeholder="0">
                                    </div>
                                </div>
                                {{-- Attribute selections --}}
                                @foreach($selectedCategoryAttributes as $attr)
                                <div>
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">{{ $attr->name }}</label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($attr->values as $val)
                                        @php $checked = $variant->attributeValues->contains('id', $val->id); @endphp
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="variants[{{ $vi }}][attribute_value_ids][]" value="{{ $val->id }}"
                                                {{ $checked ? 'checked' : '' }} class="sr-only peer">
                                            <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-primary/50 select-none">
                                                {{ $val->value }}
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        @else
                            <div id="no-variants-msg" class="p-8 text-center text-slate-400 text-sm">
                                <i class="bi bi-boxes text-4xl block mb-2 opacity-30"></i>
                                No variants added yet. Click "Add Variant" to start.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Product Images --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-black text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-images text-primary"></i> Product Images / Gallery
                    </h2>
                    @if(isset($product) && $product->images->count())
                        <div class="grid grid-cols-4 gap-3 mb-4">
                            @foreach($product->images as $img)
                            <div class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-square">
                                <img src="{{ Storage::url($img->image) }}" class="w-full h-full object-cover">
                                <form action="{{ route('admin.products.image.destroy', $img) }}" method="POST"
                                    class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-all">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white rounded-lg px-3 py-1.5 text-xs font-bold">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl p-8 cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all group">
                        <i class="bi bi-cloud-upload text-4xl text-slate-400 group-hover:text-primary transition-colors mb-2"></i>
                        <p class="text-sm font-bold text-slate-600 group-hover:text-primary transition-colors">Click to upload images</p>
                        <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP — Multiple files allowed</p>
                        <input type="file" name="images[]" multiple accept="image/*" class="hidden"
                            onchange="previewImages(this)">
                    </label>
                    <div id="image-preview" class="grid grid-cols-4 gap-3 mt-4"></div>
                </div>

                {{-- Video Link --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-black text-slate-800 mb-4 flex items-center gap-2">
                        <i class="bi bi-play-circle text-primary"></i> Video Link (Optional)
                    </h2>
                    <input type="url" name="video_link" value="{{ old('video_link', $product->video_link ?? '') }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all"
                        placeholder="https://youtube.com/watch?v=...">
                </div>

            </div>

            {{-- Right Column: Settings --}}
            <div class="space-y-5">

                {{-- Publish --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-black text-slate-800 mb-4">Publish Settings</h2>
                    <div class="space-y-3">
                        @foreach(['is_featured' => ['⭐', 'Featured Product'], 'is_best_seller' => ['🔥', 'Best Seller'], 'is_flash_deal' => ['⚡', 'Flash Deal']] as $field => [$icon, $label])
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 cursor-pointer hover:bg-primary/5 hover:border-primary/20 transition-all group">
                            <input type="checkbox" name="{{ $field }}" value="1"
                                {{ old($field, isset($product) && $product->$field ? '1' : '0') === '1' ? 'checked' : '' }}
                                class="w-4 h-4 accent-primary">
                            <span class="text-sm font-bold text-slate-700 group-hover:text-primary transition-colors">{{ $icon }} {{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    <button type="submit"
                        class="w-full mt-5 bg-primary hover:bg-primary-dark text-white font-black py-3.5 rounded-xl transition-all text-sm tracking-wide flex items-center justify-center gap-2">
                        <i class="bi bi-check-lg text-lg"></i>
                        {{ isset($product) ? 'Update Product' : 'Create Product' }}
                    </button>
                </div>

                {{-- Category --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-black text-slate-800 mb-4">Category</h2>
                    <select id="category" name="category_id" required onchange="onCategoryChange()"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 transition-all appearance-none bg-white cursor-pointer">
                        <option value="">— Select Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', isset($product) ? $product->category_id : '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->getTranslation('name','en') }}
                            </option>
                            @foreach($cat->children ?? [] as $child)
                            <option value="{{ $child->id }}" {{ old('category_id', isset($product) ? $product->category_id : '') == $child->id ? 'selected' : '' }}>
                                &nbsp;&nbsp;↳ {{ $child->getTranslation('name','en') }}
                            </option>
                            @endforeach
                        @endforeach
                    </select>

                    <div id="category-attribute-summary" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-black uppercase tracking-[0.25em] text-slate-500">Selected Category Attributes</p>
                        <div id="category-attribute-tags" class="mt-3 flex flex-wrap gap-2">
                            @foreach($selectedCategoryAttributes as $attribute)
                                <span class="rounded-full bg-white border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">{{ $attribute->name }}</span>
                            @endforeach
                            @if($selectedCategoryAttributes->isEmpty())
                                <span class="text-xs text-slate-400">Choose a category to see custom attributes here.</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-black text-slate-800">Pricing</h2>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Base Price (৳) <span class="text-red-500">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" min="0" step="0.01" required
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Discount (%)</label>
                        <input type="number" name="discount" value="{{ old('discount', $product->discount ?? 0) }}" min="0" max="100"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Low Stock Alert Threshold</label>
                        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" min="0"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all">
                    </div>
                </div>

                {{-- Brand & Model --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-black text-slate-800">Product Details</h2>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                            placeholder="e.g. Samsung, Levi's">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Model</label>
                        <input type="text" name="model" value="{{ old('model', $product->model ?? '') }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                            placeholder="e.g. Galaxy S24, Slim Fit">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Warranty Period</label>
                        <input type="text" name="warranty_period" value="{{ old('warranty_period', $product->warranty_period ?? '') }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-blue-400 transition-all"
                            placeholder="e.g. 1 Year, 6 Months">
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    window.productVariantConfig = {
        attributes: @json($attributePayload['attributes']),
        categoryAttributeMappings: @json($attributePayload['categoryAttributeMappings']),
        initialVariantIndex: {{ isset($product) ? $product->variants->count() : 0 }},
    };
</script>
@vite(['resources/js/admin/product.js'])
@endpush
@endsection
