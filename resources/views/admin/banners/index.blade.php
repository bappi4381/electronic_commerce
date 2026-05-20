@extends('admin.layouts')
@section('title', 'Banner & Hero Management')

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-600/20">
                <i class="bi bi-images text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none uppercase">Visual Canvas</h1>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Manage Hero Sliders & Promotional Banners</p>
            </div>
        </div>

        <button @click="$dispatch('open-modal')" class="bg-primary text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-primary/20 hover:bg-black transition-all flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Upload New Banner
        </button>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-10">
        {{-- Banner Matrix --}}
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Active Visual Assets</h2>
                <div class="flex gap-4">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-slate-100">Hero: {{ $banners->where('type', 'hero')->count() }}</span>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-slate-100">Promo: {{ $banners->where('type', 'promo')->count() }}</span>
                </div>
            </div>
            
            <div class="divide-y divide-slate-50">
                @forelse($banners as $banner)
                    <div class="px-10 py-8 flex flex-col lg:flex-row lg:items-center justify-between gap-8 hover:bg-slate-50 transition-colors group">
                        <div class="flex items-center gap-8 flex-grow">
                            <div class="relative w-48 h-28 bg-slate-100 rounded-[2rem] overflow-hidden shadow-inner flex-shrink-0">
                                <img src="{{ asset('storage/' . $banner->image) }}" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-[8px] font-black text-white uppercase tracking-widest bg-black/50 px-3 py-1 rounded-full">Preview</span>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-2.5 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border {{ $banner->type == 'hero' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : ($banner->type == 'promo' ? 'bg-orange-50 text-orange-600 border-orange-100' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                        {{ $banner->type }}
                                    </span>
                                    <span class="text-[10px] font-black text-slate-300">#{{ $banner->order }}</span>
                                </div>
                                <h3 class="text-sm font-black text-slate-900 truncate uppercase tracking-tighter">{{ $banner->title ?? 'Untitled Asset' }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 mt-1 truncate">{{ $banner->subtitle ?? 'No secondary caption provided' }}</p>
                                @if($banner->link)
                                    <div class="flex items-center gap-1 mt-2 text-[9px] font-black text-primary uppercase italic">
                                        <i class="bi bi-link-45deg text-sm"></i>
                                        {{ Str::limit($banner->link, 30) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <form action="{{ route('banners.toggle', $banner) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="flex items-center gap-3 px-5 py-2.5 rounded-2xl border {{ $banner->status ? 'bg-emerald-50 border-emerald-100 text-emerald-600 shadow-emerald-500/5' : 'bg-slate-100 border-slate-200 text-slate-400' }} transition-all">
                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ $banner->status ? 'Visible' : 'Hidden' }}</span>
                                    <div class="w-2 h-2 rounded-full {{ $banner->status ? 'bg-emerald-500' : 'bg-slate-400' }}"></div>
                                </button>
                            </form>
                            
                            <div class="flex items-center gap-2">
                                <button class="w-11 h-11 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form action="{{ route('banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Erase this visual asset?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-11 h-11 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:bg-red-500 hover:text-white hover:border-red-500 transition-all shadow-sm">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-24 text-center">
                        <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center text-slate-200 mx-auto mb-6 text-3xl">
                            <i class="bi bi-images"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">Visual Canvas is empty</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Upload Modal (Alpine.js) --}}
<div x-data="{ open: false }" @open-modal.window="open = true" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>
    <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-2xl relative overflow-hidden animate-slideUp">
        <div class="p-10">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white">
                        <i class="bi bi-upload"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-900">Upload Asset</h3>
                        <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Add new visual component</p>
                    </div>
                </div>
                <button @click="open = false" class="text-slate-400 hover:text-slate-900"><i class="bi bi-x-lg"></i></button>
            </div>

            <form action="{{ route('banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Asset Title</label>
                        <input type="text" name="title" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Sub Title</label>
                        <input type="text" name="subtitle" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Banner Type</label>
                        <select name="type" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all cursor-pointer">
                            <option value="hero">Main Hero Slider</option>
                            <option value="promo">Promotional Banner</option>
                            <option value="sub_banner">Sidebar/Sub Banner</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Redirect Link</label>
                        <input type="text" name="link" placeholder="https://..." class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Image Asset</label>
                    <div class="relative group">
                        <input type="file" name="image" class="absolute inset-0 opacity-0 cursor-pointer z-10" required>
                        <div class="w-full h-40 border-4 border-dashed border-slate-50 rounded-[2rem] flex flex-col items-center justify-center group-hover:bg-slate-50 group-hover:border-indigo-100 transition-all">
                            <i class="bi bi-cloud-arrow-up text-3xl text-slate-200 group-hover:text-indigo-600 transition-colors"></i>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mt-2">Drop image here or click</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-slate-900 text-white py-5 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-slate-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all">
                        Sync Asset to Canvas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
