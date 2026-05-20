@extends('admin.layouts')

@section('title', 'Refine Story')

@section('content')
<div class="p-6 lg:p-10 bg-slate-50 min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase italic">Refine Tech Story</h1>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i>
                Editing: {{ $article->title }}
            </p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('articles.index') }}" class="bg-white text-slate-900 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-sm border border-slate-100 hover:bg-slate-50 transition-all active:scale-95 flex items-center gap-3">
                <i class="bi bi-arrow-left text-lg"></i>
                Back
            </a>
            <button form="articleForm" type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/20 hover:bg-primary transition-all active:scale-95 flex items-center gap-3">
                <i class="bi bi-cloud-arrow-up text-lg"></i>
                Update Story
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-8 bg-red-500 text-white p-5 rounded-2xl shadow-xl shadow-red-500/20 space-y-2">
            @foreach($errors->all() as $error)
                <p class="font-bold uppercase tracking-widest text-[10px] flex items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i> {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('articles.update', $article->id) }}" method="POST" enctype="multipart/form-data" id="articleForm" class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        @csrf
        @method('PUT')

        {{-- Left Column: Main Editor --}}
        <div class="xl:col-span-2 space-y-8">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                            <i class="bi bi-textarea-t"></i>
                        </span>
                        Story Composition
                    </h3>
                </div>
                <div class="p-10 space-y-8">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Article Headline</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $article->title) }}" required
                            class="w-full px-6 py-5 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-black text-slate-900 placeholder:text-slate-300 text-xl italic uppercase tracking-tighter"
                            placeholder="Enter a compelling title...">
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Story Content (Rich Text)</label>
                        <div class="bg-slate-50 rounded-3xl p-4 min-h-[500px] border border-slate-100">
                            <textarea name="content" rows="20" required
                                class="w-full bg-transparent border-0 rounded-2xl px-4 py-4 text-sm font-medium focus:ring-0 transition-all text-slate-600 leading-relaxed"
                                placeholder="Start writing your story here...">{{ old('content', $article->content) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Metadata & Assets --}}
        <div class="space-y-8">
            {{-- Status & Category --}}
            <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-10 text-white relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl"></div>
                <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-10 flex items-center gap-3 relative z-10">
                    <i class="bi bi-gear-fill text-primary"></i>
                    Publishing Parameters
                </h3>

                <div class="space-y-8 relative z-10">
                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <i class="bi bi-tag-fill"></i> Taxonomy Category
                        </label>
                        <select name="category_id" required
                            class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl focus:border-primary/50 focus:ring-0 transition-all font-bold text-sm text-white appearance-none cursor-pointer">
                            <option value="" class="bg-slate-900 text-slate-400">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $article->category_id) == $category->id ? 'selected' : '' }} class="bg-slate-900 text-white">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <i class="bi bi-calendar-event"></i> Publication Date
                        </label>
                        <input type="date" name="published_at" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d')) }}"
                            class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl focus:border-primary/50 focus:ring-0 transition-all font-bold text-sm text-white">
                    </div>

                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <i class="bi bi-eye-fill"></i> Visibility Status
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="status" value="draft" class="peer hidden" {{ old('status', $article->status) == 'draft' ? 'checked' : '' }}>
                                <div class="px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-center font-black text-[9px] uppercase tracking-widest group-hover:bg-white/10 transition-all peer-checked:bg-slate-700 peer-checked:border-slate-500 peer-checked:text-white">Draft</div>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="status" value="published" class="peer hidden" {{ old('status', $article->status) == 'published' ? 'checked' : '' }}>
                                <div class="px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-center font-black text-[9px] uppercase tracking-widest group-hover:bg-white/10 transition-all peer-checked:bg-primary peer-checked:border-primary peer-checked:text-white">Publish</div>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <i class="bi bi-link-45deg"></i> Permanent Link (Slug)
                        </label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $article->slug) }}" required
                            class="w-full px-6 py-4 bg-white/5 border border-white/20 rounded-2xl font-bold text-xs text-slate-400 outline-none">
                    </div>
                </div>
            </div>

            {{-- Feature Image --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                            <i class="bi bi-image"></i>
                        </span>
                        Visual Asset
                    </h3>
                </div>
                <div class="p-10 space-y-6">
                    <div id="image-drop-area" class="border-2 border-dashed border-slate-200 rounded-[2rem] p-10 text-center hover:border-primary hover:bg-slate-50 transition-all cursor-pointer group hidden">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-4 group-hover:text-primary group-hover:scale-110 transition-all">
                            <i class="bi bi-cloud-arrow-up text-3xl"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Featured Image</p>
                        <input type="file" name="image" id="image-input" class="hidden" accept="image/*">
                    </div>
                    <div id="image-preview" class="rounded-3xl overflow-hidden shadow-lg border border-slate-100 aspect-video bg-slate-50 relative group">
                        <img src="{{ asset('storage/' . $article->image) }}" class="w-full h-full object-cover">
                        <button type="button" onclick="document.getElementById('image-input').click()" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-black uppercase tracking-widest">
                            Change Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // ✅ Auto Slug Generation (only if title changed)
    document.getElementById('title').addEventListener('input', function() {
        let slug = this.value
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/--+/g, '-')
            .trim('-');
        document.getElementById('slug').value = slug;
    });

    // ✅ Image Preview Logic
    const input = document.getElementById('image-input');
    const preview = document.getElementById('image-preview');

    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                    <button type="button" onclick="document.getElementById('image-input').click()" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-black uppercase tracking-widest">
                                        Change Image
                                    </button>`;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>
@endsection
