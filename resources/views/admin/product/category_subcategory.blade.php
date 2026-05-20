@extends('admin.layouts')
@section('title', 'Categories & Subcategories')

@section('content')
<div class="space-y-10 pb-20">
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
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Visual Asset</label>
                        <input type="file" name="image" accept="image/*" class="w-full text-[10px] text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
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
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-slate-100 group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-white border border-slate-100 flex-shrink-0">
                                    @if($category->image)
                                        <img src="{{ asset('storage/' . $category->image) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-200"><i class="bi bi-tag"></i></div>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-xs font-black text-slate-800 block leading-none">{{ $category->name }}</span>
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1 block">{{ $category->subcategories->count() }} Sub-Items</span>
                                </div>
                            </div>
                            <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete category?')" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
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
                                <form action="{{ route('subcategories.destroy', $sub->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete subcategory?')" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                        <i class="bi bi-x-lg text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
