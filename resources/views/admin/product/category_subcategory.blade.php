@extends('admin.layouts')
@section('title', 'Categories & Subcategories')

@section('content')
<div class="space-y-10 pb-20" x-data="{ 
    editCategoryModal: false, 
    editSubcategoryModal: false,
    editingCategory: { id: null, name: '', image: '', icon: '', color: '' },
    editingSubcategory: { id: null, name: '', category_id: '' },
    openCategoryEdit(cat) {
        this.editingCategory = { ...cat };
        this.editCategoryModal = true;
    },
    openSubcategoryEdit(sub) {
        this.editingSubcategory = { ...sub };
        this.editSubcategoryModal = true;
    }
}">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white">
            <i class="bi bi-diagram-3 text-xl"></i>
        </div>
        <div>
            <h1 class="text-2xl font-black text-slate-900 leading-none uppercase tracking-tighter">Taxonomy Management</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Configure Categories & Sub-Hierarchies</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl">
                <i class="bi bi-check-lg"></i>
            </div>
            <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex flex-col gap-2 animate-fadeIn">
            @foreach($errors->all() as $error)
                <div class="flex items-center gap-4">
                    <div class="w-6 h-6 bg-red-500 rounded-lg flex items-center justify-center text-white text-xs">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <p class="text-xs font-bold text-red-700">{{ $error }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        {{-- CATEGORIES SECTION --}}
        <div class="space-y-6">
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary text-sm">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Add New Category</h2>
                </div>
                
                <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Category Designation</label>
                        <input type="text" name="name" placeholder="e.g. Smartphones" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Icon Class (Bootstrap)</label>
                            <input type="text" name="icon" placeholder="bi-phone" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Color Theme (Tailwind)</label>
                            <input type="text" name="color" placeholder="blue-600" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                        </div>
                    </div>
                    <input type="hidden" name="type" value="product">
                    <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Commit Category
                    </button>
                </form>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em] mb-6">Existing Categories</h2>
                <div class="space-y-4">
                    @foreach($categories as $category)
                        @php
                            $iconClass = $category->icon ?? 'bi-tag';
                            if ($category->icon && !str_starts_with($category->icon, 'bi-')) {
                                $iconClass = 'bi-' . $category->icon;
                            }
                        @endphp
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-100 group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-white border border-slate-100 flex items-center justify-center text-xl shadow-sm" style="color: {{ str_contains($category->color, '#') ? $category->color : '' }}">
                                     <i class="{{ $iconClass }} {{ !str_contains($category->color, '#') ? 'text-'.$category->color : '' }}"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-black text-slate-800 block leading-none">{{ $category->name }}</span>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ $category->subcategories->count() }} Sub-Items</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        <span class="text-[8px] font-black text-primary uppercase tracking-widest">{{ $category->products_count }} Products</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openCategoryEdit({{ json_encode($category) }})" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-100 text-slate-400 hover:text-primary hover:border-primary/20 transition-all opacity-0 group-hover:opacity-100">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete category?')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-100 text-slate-300 hover:text-red-500 hover:border-red-100 transition-all opacity-0 group-hover:opacity-100">
                                        <i class="bi bi-trash3 text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- SUBCATEGORIES SECTION --}}
        <div class="space-y-6">
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-600 text-sm">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Add Sub-Hierarchy</h2>
                </div>
                
                <form action="{{ route('subcategories.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Parent Entity</label>
                        <select name="category_id" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer" required>
                            <option value="">Select Parent...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Subcategory Name</label>
                        <input type="text" name="name" placeholder="e.g. Flagship Models" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Link Sub-Hierarchy
                    </button>
                </form>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em] mb-6">Subcategory Mapping</h2>
                <div class="space-y-3">
                    @foreach($categories as $category)
                        @foreach($category->subcategories as $sub)
                            <div class="flex items-center justify-between p-3 bg-indigo-50/30 rounded-xl border border-indigo-100/50 group">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                                    <div>
                                        <span class="text-[10px] font-black text-slate-800 block">{{ $sub->name }}</span>
                                        <span class="text-[7px] font-black text-indigo-400 uppercase tracking-widest block mt-0.5">Under: {{ $category->name }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="openSubcategoryEdit({{ json_encode($sub) }})" class="text-slate-300 hover:text-indigo-600 transition-colors opacity-0 group-hover:opacity-100">
                                        <i class="bi bi-pencil-square text-xs"></i>
                                    </button>
                                    <form action="{{ route('subcategories.destroy', $sub->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Delete subcategory?')" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Category Edit Modal --}}
    <div x-show="editCategoryModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div x-show="editCategoryModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="editCategoryModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        
        <div x-show="editCategoryModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Adjust Category</h3>
                    <button @click="editCategoryModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                </div>

                <form :action="'{{ route('categories.update', ['category' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', editingCategory.id)" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Category Designation</label>
                        <input type="text" name="name" x-model="editingCategory.name" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Icon Class</label>
                            <input type="text" name="icon" x-model="editingCategory.icon" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Color Theme</label>
                            <input type="text" name="color" x-model="editingCategory.color" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-primary text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Update Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Subcategory Edit Modal --}}
    <div x-show="editSubcategoryModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div x-show="editSubcategoryModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="editSubcategoryModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        
        <div x-show="editSubcategoryModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Adjust Sub-Hierarchy</h3>
                    <button @click="editSubcategoryModal = false" class="text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
                </div>

                <form :action="'{{ route('subcategories.update', ['subcategory' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', editingSubcategory.id)" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Parent Entity</label>
                        <select name="category_id" x-model="editingSubcategory.category_id" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all cursor-pointer" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Subcategory Name</label>
                        <input type="text" name="name" x-model="editingSubcategory.name" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-500/10 outline-none transition-all" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Update Sub-Hierarchy
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
