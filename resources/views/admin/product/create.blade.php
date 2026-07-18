@extends('admin.layouts')
@section('title', isset($product) ? 'Edit Product' : 'Add New Product')

@push('styles')
<style>
    .variant-row { animation: fadeIn .3s ease; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .tab-btn.active { background: #3b82f6; color: #fff; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-4xl mx-auto" x-data="{ step: 1, maxStep: 3 }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                {{ isset($product) ? 'Edit Product' : 'Add New Product' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Follow the steps to complete the product setup.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="flex items-center gap-2 text-sm font-bold text-slate-600 bg-white border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
            <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Stepper UI --}}
    <div class="mb-8 relative pt-4">
        <div class="absolute left-0 top-1/2 w-full h-1 bg-slate-200 rounded-full z-0 -translate-y-1/2 mt-2"></div>
        <div class="absolute left-0 top-1/2 h-1 bg-primary rounded-full z-0 transition-all duration-500 -translate-y-1/2 mt-2" 
             :style="'width: ' + ((step - 1) * 50) + '%'"></div>
        
        <div class="relative z-10 flex justify-between">
            <!-- Step 1 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 1 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">1</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 1 ? 'text-primary' : 'text-slate-400'">Basic Info</span>
            </div>
            <!-- Step 2 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 2 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">2</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 2 ? 'text-primary' : 'text-slate-400'">Pricing & Stock</span>
            </div>
            <!-- Step 3 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 3 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">3</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 3 ? 'text-primary' : 'text-slate-400'">Media & Publish</span>
            </div>
        </div>
    </div>

    @php
        $selectedCategoryAttributes = isset($selectedCategoryAttributes) && $selectedCategoryAttributes->isNotEmpty()
            ? $selectedCategoryAttributes
            : $attributes;
    @endphp

    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}"
        method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        @if(isset($product)) @method('PUT') @endif

        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-6 md:p-8">

            {{-- STEP 1: Basic Information --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                
                <div class="mb-6 pb-6 border-b border-slate-100">
                    <h2 class="text-xl font-black text-slate-800">1. Basic Information</h2>
                    <p class="text-sm text-slate-500 mt-1">Start with the primary details of the product.</p>
                </div>

                <div class="space-y-6">
                    {{-- Category --}}
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Category <span class="text-red-500">*</span></label>
                        <select id="category" name="category_id" required onchange="onCategoryChange()"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all appearance-none bg-white cursor-pointer hover:border-primary/40">
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
                        <div id="category-attribute-summary" class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-500 mb-2">Selected Category Attributes</p>
                            <div id="category-attribute-tags" class="flex flex-wrap gap-2">
                                @foreach($selectedCategoryAttributes as $attribute)
                                    <span class="rounded-full bg-white border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">{{ $attribute->name }}</span>
                                @endforeach
                                @if($selectedCategoryAttributes->isEmpty())
                                    <span class="text-xs text-slate-400">Choose a category to see custom attributes here.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Name & Description Tabs --}}
                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="flex border-b border-slate-100 bg-slate-50">
                            <button type="button" onclick="switchTab('en')" id="tab-en"
                                class="tab-btn active px-6 py-3 text-sm font-bold rounded-none transition-all flex items-center gap-2">
                                🇬🇧 English
                            </button>
                            <button type="button" onclick="switchTab('bn')" id="tab-bn"
                                class="tab-btn px-6 py-3 text-sm font-bold rounded-none text-slate-500 transition-all flex items-center gap-2 hover:bg-slate-100">
                                🇧🇩 বাংলা
                            </button>
                        </div>
                        <div id="content-en" class="tab-content active p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Product Name (English) <span class="text-red-500">*</span></label>
                                <input type="text" name="name_en" value="{{ old('name_en', isset($product) ? $product->getTranslation('name','en') : '') }}"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all hover:border-primary/40"
                                    placeholder="e.g. Premium Cotton T-Shirt" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">Description (English)</label>
                                <textarea name="description_en" rows="4"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 outline-none focus:border-primary transition-all hover:border-primary/40"
                                    placeholder="Write a detailed product description in English...">{{ old('description_en', isset($product) ? $product->getTranslation('description','en') : '') }}</textarea>
                            </div>
                        </div>
                        <div id="content-bn" class="tab-content p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">পণ্যের নাম (বাংলা)</label>
                                <input type="text" name="name_bn" value="{{ old('name_bn', isset($product) ? $product->getTranslation('name','bn') : '') }}"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all hover:border-primary/40"
                                    placeholder="যেমন: প্রিমিয়াম কটন টি-শার্ট">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wider">বিবরণ (বাংলা)</label>
                                <textarea name="description_bn" rows="4"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 outline-none focus:border-primary transition-all hover:border-primary/40"
                                    placeholder="বাংলায় পণ্যের বিস্তারিত বিবরণ লিখুন...">{{ old('description_bn', isset($product) ? $product->getTranslation('description','bn') : '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Brand & Model Details (Optional) --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Brand <span class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                            <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                                placeholder="e.g. Samsung">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Model <span class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                            <input type="text" name="model" value="{{ old('model', $product->model ?? '') }}"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                                placeholder="e.g. Galaxy S24">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Warranty <span class="text-slate-400 font-normal normal-case">(Optional)</span></label>
                            <input type="text" name="warranty_period" value="{{ old('warranty_period', $product->warranty_period ?? '') }}"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                                placeholder="e.g. 1 Year">
                        </div>
                    </div>
                    
                    <div class="pt-4 flex justify-end">
                        <button type="button" @click="step = 2" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-3.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-md">
                            Next Step <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 2: Pricing & Inventory --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                
                <div class="mb-6 pb-6 border-b border-slate-100">
                    <h2 class="text-xl font-black text-slate-800">2. Pricing & Stock</h2>
                    <p class="text-sm text-slate-500 mt-1">Set the price, discounts, and configure variants.</p>
                </div>

                <div class="space-y-6">
                    {{-- Base Pricing --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Base Price (৳) <span class="text-red-500">*</span></label>
                            <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" min="0" step="0.01" required
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                                placeholder="0.00">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Discount (%)</label>
                            <input type="number" name="discount" value="{{ old('discount', $product->discount ?? 0) }}" min="0" max="100"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                                placeholder="0">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" min="0"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40">
                        </div>
                    </div>

                    {{-- Product Variants Manager --}}
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50 shadow-sm">
                        <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-b border-slate-200">
                            <div>
                                <h3 class="text-sm font-black text-slate-800">Product Variants</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Add variants to manage stock properly (e.g. Size M, Color Red).</p>
                            </div>
                            <button type="button" onclick="addVariant()"
                                class="flex items-center gap-2 text-sm font-bold bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl hover:bg-slate-100 transition-all shadow-sm">
                                <i class="bi bi-plus-lg text-primary"></i> Add Variant
                            </button>
                        </div>

                        <div id="variants-container" class="divide-y divide-slate-100 bg-white">
                            @if(isset($product) && $product->variants->count())
                                @foreach($product->variants as $vi => $variant)
                                <div class="variant-row p-6 space-y-4" id="variant-{{ $vi }}">
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-50">
                                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest bg-slate-100 px-2.5 py-1 rounded-md">Variant #{{ $vi + 1 }}</span>
                                        <button type="button" onclick="removeVariant({{ $vi }})"
                                            class="text-red-400 hover:text-red-600 transition-colors text-xs font-bold flex items-center gap-1.5">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">SKU</label>
                                            <input type="text" name="variants[{{ $vi }}][sku]" value="{{ $variant->sku }}"
                                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-semibold outline-none focus:border-primary transition-all"
                                                placeholder="e.g. TSH-RED-M">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Price Override (৳)</label>
                                            <input type="number" name="variants[{{ $vi }}][price]" value="{{ $variant->price }}" min="0" step="0.01"
                                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-semibold outline-none focus:border-primary transition-all"
                                                placeholder="Leave blank = base price">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Stock <span class="text-red-500">*</span></label>
                                            <input type="number" name="variants[{{ $vi }}][stock]" value="{{ $variant->stock }}" min="0" required
                                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-semibold outline-none focus:border-primary transition-all bg-slate-50"
                                                placeholder="0">
                                        </div>
                                    </div>
                                    {{-- Attribute selections --}}
                                    @if($selectedCategoryAttributes->isNotEmpty())
                                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mt-2 space-y-3">
                                        @foreach($selectedCategoryAttributes as $attr)
                                        <div>
                                            <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block mb-2">{{ $attr->name }}</label>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($attr->values as $val)
                                                @php $checked = $variant->attributeValues->contains('id', $val->id); @endphp
                                                <label class="cursor-pointer">
                                                    <input type="checkbox" name="variants[{{ $vi }}][attribute_value_ids][]" value="{{ $val->id }}"
                                                        {{ $checked ? 'checked' : '' }} class="sr-only peer">
                                                    <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 bg-white text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-primary/50 select-none shadow-sm">
                                                        {{ $val->value }}
                                                    </span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div id="no-variants-msg" class="p-10 text-center text-slate-400 flex flex-col items-center justify-center">
                                    <i class="bi bi-boxes text-4xl mb-3 opacity-20"></i>
                                    <p class="text-sm font-semibold text-slate-500">No variants added yet.</p>
                                    <p class="text-xs mt-1">Click "Add Variant" to set up your stock.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between items-center">
                        <button type="button" @click="step = 1" class="text-slate-500 hover:text-slate-800 font-bold text-sm flex items-center gap-2 transition-colors">
                            <i class="bi bi-arrow-left"></i> Previous
                        </button>
                        <button type="button" @click="step = 3" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-3.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-md">
                            Next Step <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Media & Publishing --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
                
                <div class="mb-6 pb-6 border-b border-slate-100">
                    <h2 class="text-xl font-black text-slate-800">3. Media & Publishing</h2>
                    <p class="text-sm text-slate-500 mt-1">Upload images and set product visibility.</p>
                </div>

                <div class="space-y-8">
                    {{-- Images --}}
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-3 flex items-center gap-2">
                            <i class="bi bi-images text-primary"></i> Product Images
                        </label>
                        @if(isset($product) && $product->images->count())
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-4">
                                @foreach($product->images as $img)
                                <div class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-square shadow-sm">
                                    <img src="{{ Storage::url($img->image) }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-all backdrop-blur-sm">
                                        <button type="button" onclick="if(confirm('Remove this image?')) document.getElementById('delete-img-{{ $img->id }}').submit();" class="bg-red-500 hover:bg-red-600 text-white rounded-lg px-3 py-1.5 text-[11px] font-bold shadow-lg transition-colors flex items-center gap-1.5">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <label class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 bg-slate-50/50 rounded-2xl p-10 cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all group">
                            <div class="w-14 h-14 bg-white rounded-full shadow-sm border border-slate-100 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="bi bi-cloud-upload text-2xl text-primary"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-700">Click to upload images</p>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-1">PNG, JPG, WEBP</p>
                            <input type="file" name="images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                        </label>
                        <div id="image-preview" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-4"></div>
                    </div>

                    {{-- Video Link --}}
                    <div>
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-2 flex items-center gap-2">
                            <i class="bi bi-play-circle text-primary"></i> Video Link <span class="text-slate-400 font-normal normal-case">(Optional)</span>
                        </label>
                        <input type="url" name="video_link" value="{{ old('video_link', $product->video_link ?? '') }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all hover:border-primary/40"
                            placeholder="https://youtube.com/watch?v=...">
                    </div>

                    {{-- Publish Settings --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 shadow-sm">
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-4">Publish Settings</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach(['is_featured' => ['⭐', 'Featured Product'], 'is_best_seller' => ['🔥', 'Best Seller'], 'is_flash_deal' => ['⚡', 'Flash Deal']] as $field => [$icon, $label])
                            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-primary/40 hover:shadow-sm transition-all group">
                                <input type="checkbox" name="{{ $field }}" value="1"
                                    {{ old($field, isset($product) && $product->$field ? '1' : '0') === '1' ? 'checked' : '' }}
                                    class="w-4 h-4 accent-primary">
                                <span class="text-sm font-bold text-slate-700">{{ $icon }} {{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 flex justify-between items-center border-t border-slate-100">
                        <button type="button" @click="step = 2" class="text-slate-500 hover:text-slate-800 font-bold text-sm flex items-center gap-2 transition-colors">
                            <i class="bi bi-arrow-left"></i> Previous
                        </button>
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3.5 rounded-xl text-sm font-black flex items-center gap-2 transition-all shadow-lg shadow-primary/30 hover:-translate-y-0.5">
                            <i class="bi bi-check-lg text-lg"></i> {{ isset($product) ? 'Update Product' : 'Create Product' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@if(isset($product) && $product->images->count())
    @foreach($product->images as $img)
        <form id="delete-img-{{ $img->id }}" action="{{ route('admin.products.image.destroy', $img) }}" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif

@push('scripts')
<script>
    window.productVariantConfig = {
        attributes: @json($attributePayload['attributes']),
        categoryAttributeMappings: @json($attributePayload['categoryAttributeMappings']),
        initialVariantIndex: {{ isset($product) ? $product->variants->count() : 0 }},
    };
    
    // Add simple form validation for steps
    document.getElementById('productForm').addEventListener('submit', function(e) {
        // If there are invalid fields, find which step they belong to
        const invalidFields = this.querySelectorAll(':invalid');
        if(invalidFields.length > 0) {
            e.preventDefault();
            const firstInvalid = invalidFields[0];
            // Find which step contains this field
            const stepContainer = firstInvalid.closest('[x-show]');
            if(stepContainer) {
                const stepNum = stepContainer.getAttribute('x-show').match(/\d+/)[0];
                // Access Alpine instance to change step
                const alpineEl = document.querySelector('[x-data]');
                if(alpineEl && alpineEl.__x) {
                    alpineEl.__x.$data.step = parseInt(stepNum);
                }
            }
            firstInvalid.focus();
        }
    });
</script>
@vite(['resources/js/admin/product.js'])
@endpush
@endsection
