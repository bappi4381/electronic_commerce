@extends('admin.layouts')
@section('title', isset($product) ? 'Refine Product' : 'Onboard Product')

@section('content')
<div class="max-w-7xl mx-auto pb-20" x-data="productForm()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-slate-900 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-slate-900/20">
                <i class="bi bi-box-seam text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">
                    {{ isset($product) ? 'Refine Asset' : 'New Product' }}
                </h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="w-1 h-1 bg-primary rounded-full"></span>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Inventory Management</p>
                </div>
            </div>
        </div>
        
        {{-- Navigation Stepper --}}
        <div class="flex bg-slate-100/50 p-1.5 rounded-3xl border border-slate-200/50 backdrop-blur-xl">
            <template x-for="(tab, index) in tabs" :key="index">
                <button @click="currentTab = tab.id" 
                        :class="currentTab === tab.id ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
                        class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all duration-300 flex items-center gap-2">
                    <i :class="tab.icon"></i>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>
    </div>

    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" 
          method="POST" enctype="multipart/form-data" id="product-form">
        @csrf
        @if(isset($product)) @method('PUT') @endif

        {{-- Tab 1: Primary Configuration --}}
        <div x-show="currentTab === 'primary'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-1 space-y-8">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-primary mb-6">01. Essential Info</h4>
                        <div class="space-y-6">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Product Title</label>
                                <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. iPhone 17" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" required>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Category</label>
                                <select name="category_id" id="category-select" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all cursor-pointer" required>
                                    <option value="">Select...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Sub-Category</label>
                                <select name="subcategory_id" id="subcategory-select" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all cursor-pointer">
                                    <option value="">Select...</option>
                                    @foreach($subcategories as $sub)
                                        <option value="{{ $sub->id }}" {{ old('subcategory_id', $product->subcategory_id ?? '') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-indigo-600 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-indigo-600/30">
                        <i class="bi bi-lightbulb text-2xl text-indigo-200"></i>
                        <h5 class="mt-4 text-xs font-black uppercase tracking-widest">Table Support</h5>
                        <p class="mt-2 text-[10px] font-medium opacity-80 leading-relaxed">The main editor supports professional table formatting. Use it to paste your full specification sheets directly from Excel or Google Sheets.</p>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                        <div class="flex items-center gap-4 mb-8 pb-4 border-b border-slate-50">
                            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
                                <i class="bi bi-list-columns-reverse text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-700">02. Full Specifications Sheet</h4>
                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Main Product Content & Tables</p>
                            </div>
                        </div>
                        <div class="rounded-2xl overflow-hidden border border-slate-100">
                            <textarea name="description" id="tiny-editor">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Technical Attributes --}}
        <div x-show="currentTab === 'attributes'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-cloak>
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center justify-between mb-10 pb-6 border-b border-slate-50">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                            <i class="bi bi-cpu text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-700">03. Technical Matrix</h4>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Dynamic Key-Value Attributes</p>
                        </div>
                    </div>
                    <button type="button" @click="addSpecGroup()" class="bg-indigo-600 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-600/20 hover:scale-105 transition-all">
                        + Add Section
                    </button>
                </div>

                <div class="space-y-8">
                    {{-- Quick Specs --}}
                    <div class="bg-slate-50/50 p-8 rounded-[2rem] grid grid-cols-2 lg:grid-cols-4 gap-6 border border-slate-100">
                        @foreach([['Brand','brand'],['Model','model'],['RAM','ram'],['Storage','storage'],['Battery','battery_capacity'],['Screen','screen_size'],['OS','operating_system'],['Color','color'],['Warranty','warranty_period']] as $qs)
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">{{ $qs[0] }}</label>
                                <input type="text" name="{{ $qs[1] }}" value="{{ old($qs[1], $product->{$qs[1]} ?? '') }}" class="w-full bg-white border-none rounded-lg px-4 py-3 text-xs font-bold shadow-sm focus:ring-1 focus:ring-primary/10">
                            </div>
                        @endforeach
                    </div>

                    {{-- Dynamic Matrix --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <template x-for="(group, gIndex) in specifications" :key="gIndex">
                            <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-sm relative group/card">
                                <button type="button" @click="removeSpecGroup(gIndex)" class="absolute -top-3 -right-3 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-all shadow-xl">
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>
                                
                                <div class="flex items-center justify-between mb-6">
                                    <input type="text" x-model="group.title" :name="`specifications[${gIndex}][title]`" placeholder="Section Title (e.g. Display)" class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-600 focus:ring-2 focus:ring-primary/10 w-2/3">
                                    <button type="button" @click="addSpecRow(gIndex)" class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">+ Feature</button>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(row, rIndex) in group.attributes" :key="rIndex">
                                        <div class="flex items-center gap-3 group/row">
                                            <input type="text" x-model="row.label" :name="`specifications[${gIndex}][attributes][${rIndex}][label]`" placeholder="Spec" class="w-1/3 bg-slate-50 border-none rounded-lg px-4 py-2 text-[10px] font-bold text-slate-500">
                                            <input type="text" x-model="row.value" :name="`specifications[${gIndex}][attributes][${rIndex}][value]`" placeholder="Value" class="flex-1 bg-slate-50 border-none rounded-lg px-4 py-2 text-[10px] font-bold text-slate-900">
                                            <button type="button" @click="removeSpecRow(gIndex, rIndex)" class="text-red-300 opacity-0 group-hover/row:opacity-100 transition-all"><i class="bi bi-dash-circle"></i></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 3: Economics & Media --}}
        <div x-show="currentTab === 'economics'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-8">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-cyan-500 mb-8">04. Economics</h4>
                        <div class="space-y-6">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Inventory</label>
                                <input type="number" name="stock" value="{{ old('stock', $product->stock ?? '') }}" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-black outline-none" required>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Retail Price (TK)</label>
                                <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" step="0.01" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-black outline-none" required>
                            </div>
                        </div>

                        {{-- Promotion Settings --}}
                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 space-y-4">
                            <h5 class="text-[8px] font-black uppercase tracking-widest text-slate-400 ml-1">Promotion Status</h5>
                            <div class="flex items-center justify-between px-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                                        <i class="bi bi-star-fill text-sm"></i>
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-tight text-slate-700">Featured Product</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between px-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                                        <i class="bi bi-lightning-charge-fill text-sm"></i>
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-tight text-slate-700">Best Seller</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_best_seller" value="1" {{ old('is_best_seller', $product->is_best_seller ?? false) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary text-white py-6 rounded-[2rem] font-black text-[12px] uppercase tracking-[0.3em] shadow-2xl shadow-primary/40 hover:bg-black transition-all">
                        Finalize Product
                    </button>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 h-full">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-purple-500 mb-8">05. Visual Portfolio</h4>
                        <div id="image-drop-area" class="border-4 border-dashed border-slate-100 rounded-[2.5rem] p-12 text-center group cursor-pointer hover:border-primary/20 hover:bg-slate-50/50 transition-all h-[300px] flex flex-col items-center justify-center">
                            <i class="bi bi-images text-5xl text-slate-200 group-hover:text-primary transition-colors"></i>
                            <h4 class="mt-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Asset Sync</h4>
                            <input type="file" name="images[]" accept="image/*" multiple class="hidden" id="image-input">
                        </div>
                        <div id="image-preview" class="grid grid-cols-4 lg:grid-cols-6 gap-4 mt-8">
                            @if(isset($product) && $product->images)
                                @foreach($product->images as $img)
                                    <div class="aspect-square rounded-xl border border-slate-50 overflow-hidden shadow-sm relative group/img">
                                        <img src="{{ asset('storage/' . $img->image) }}" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-all flex items-center justify-center">
                                            <i class="bi bi-check text-white"></i>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
function productForm() {
    return {
        currentTab: 'primary',
        tabs: [
            { id: 'primary', label: 'Identity & Specs', icon: 'bi-file-earmark-text' },
            { id: 'attributes', label: 'Technical Matrix', icon: 'bi-cpu' },
            { id: 'economics', label: 'Economics & Media', icon: 'bi-cash-coin' }
        ],
        specifications: @json(old('specifications', $product->specifications ?? [])) || [],

        init() {
            if (this.specifications.length === 0) {
                this.addSpecGroup();
            }
        },

        addSpecGroup() {
            this.specifications.push({
                title: '',
                attributes: [{ label: '', value: '' }]
            });
        },

        removeSpecGroup(index) {
            this.specifications.splice(index, 1);
        },

        addSpecRow(gIndex) {
            this.specifications[gIndex].attributes.push({ label: '', value: '' });
        },

        removeSpecRow(gIndex, rIndex) {
            this.specifications[gIndex].attributes.splice(rIndex, 1);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    ClassicEditor
        .create(document.querySelector('#tiny-editor'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'insertTable', 'blockQuote', 'undo', 'redo'],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            }
        })
        .then(editor => {
            editor.model.document.on('change:data', () => {
                document.querySelector('#tiny-editor').value = editor.getData();
            });
        })
        .catch(error => {
            console.error(error);
        });

    const categorySelect = document.getElementById('category-select');
    const subcategorySelect = document.getElementById('subcategory-select');
    let oldSubcategory = "{{ old('subcategory_id', $product->subcategory_id ?? '') }}";

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if(!categoryId) {
            subcategorySelect.innerHTML = '<option value="">Select Category</option>';
            return;
        }
        fetch("{{ route('get.subcategories') }}?category_id=" + categoryId)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Select Subcategory</option>';
                data.forEach(sub => {
                    options += `<option value="${sub.id}" ${sub.id == oldSubcategory ? 'selected' : ''}>${sub.name}</option>`;
                });
                subcategorySelect.innerHTML = options;
            });
    });

    const dropArea = document.getElementById('image-drop-area');
    const input = document.getElementById('image-input');
    const preview = document.getElementById('image-preview');

    dropArea.addEventListener('click', () => input.click());
    input.addEventListener('change', function () {
        preview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'aspect-square rounded-xl border border-primary/20 overflow-hidden shadow-sm';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                preview.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    });
});
</script>

<style>
    [x-cloak] { display: none !important; }
    .ck-editor__editable { min-height: 400px !important; border-radius: 0 0 1.5rem 1.5rem !important; background: #fff !important; border-color: #f1f5f9 !important; padding: 2rem !important; }
    .ck-toolbar { border-radius: 1.5rem 1.5rem 0 0 !important; background: #fff !important; border-color: #f1f5f9 !important; padding: 0.5rem !important; }
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
@endsection
