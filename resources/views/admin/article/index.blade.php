@extends('admin.layouts')

@section('title', 'Editorial Content')

@section('content')
<div class="p-6 lg:p-10 bg-slate-50 min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase italic">Tech Insights Manager</h1>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                <i class="bi bi-journal-text text-primary"></i>
                Curate and manage your platform's editorial stories
            </p>
        </div>
        <div class="flex items-center gap-4">
            <form action="{{ route('articles.index') }}" method="GET" class="relative group hidden sm:block">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles..." 
                       class="bg-white border-0 rounded-2xl pl-12 pr-6 py-4 text-xs font-bold w-64 focus:ring-2 focus:ring-primary/20 transition-all shadow-sm">
            </form>
            <a href="{{ route('articles.create') }}" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/20 hover:bg-primary transition-all active:scale-95 flex items-center gap-3">
                <i class="bi bi-plus-lg text-lg"></i>
                Create Story
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-8 bg-green-500 text-white p-5 rounded-2xl shadow-xl shadow-green-500/20 flex items-center gap-4">
            <i class="bi bi-check-circle-fill text-2xl"></i>
            <span class="font-bold uppercase tracking-widest text-xs">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center gap-6">
            <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-2xl">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Stories</p>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ \App\Models\Article::count() }}</h3>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center gap-6">
            <div class="w-14 h-14 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-2xl">
                <i class="bi bi-send-check"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Published</p>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ \App\Models\Article::where('status', 'published')->count() }}</h3>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center gap-6">
            <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl">
                <i class="bi bi-chat-left-dots"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">User Reactions</p>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ \App\Models\BlogReaction::count() }}</h3>
            </div>
        </div>
    </div>

    {{-- Articles Table --}}
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-50">
                        <th class="px-10 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Story Overview</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Category</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Engagement</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Status</th>
                        <th class="px-10 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($articles as $article)
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 rounded-2xl border border-slate-100 overflow-hidden shrink-0 bg-slate-50">
                                        @if($article->image)
                                            <img src="{{ asset('storage/' . $article->image) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-200 text-2xl">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-slate-900 group-hover:text-primary transition-colors leading-tight uppercase italic">{{ Str::limit($article->title, 50) }}</p>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter italic">Published: {{ optional($article->published_at)->format('d M, Y') ?? 'Not set' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-4 py-1.5 bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-widest rounded-lg border border-slate-200">
                                    {{ $article->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-center gap-4">
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <i class="bi bi-heart-fill text-[10px] text-red-400"></i>
                                        <span class="text-xs font-black">{{ $article->reactions()->count() }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-400">
                                        <i class="bi bi-chat-dots-fill text-[10px] text-blue-400"></i>
                                        <span class="text-xs font-black">{{ $article->comments()->count() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                @php $status = $article->status ?? 'draft'; @endphp
                                <span class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] shadow-sm
                                    {{ $status === 'published' ? 'bg-green-500/10 text-green-600 border border-green-200' : 'bg-slate-500/10 text-slate-500 border border-slate-200' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <div class="flex justify-end items-center gap-3">
                                    <a href="{{ route('frontend.articles.show', $article->slug) }}" target="_blank" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-slate-900 hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('articles.edit', $article) }}" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Archive this story?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-32 text-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                                    <i class="bi bi-journal-x text-4xl"></i>
                                </div>
                                <h4 class="text-xl font-black text-slate-900 uppercase tracking-tighter italic">No Stories Curated</h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">Start sharing your tech insights with the world.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($articles->hasPages())
    <div class="mt-10 flex justify-center">
        {{ $articles->links() }}
    </div>
    @endif
</div>
@endsection
