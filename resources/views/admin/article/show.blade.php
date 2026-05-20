@extends('admin.layouts')

@section('title', 'Review Story: ' . $article->title)

@section('content')
<div class="p-6 lg:p-10 bg-slate-50 min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase italic">Review Insight</h1>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                <i class="bi bi-eye-fill text-primary"></i>
                Detailed inspection of editorial content
            </p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('articles.index') }}" class="bg-white text-slate-900 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-sm border border-slate-100 hover:bg-slate-50 transition-all active:scale-95 flex items-center gap-3">
                <i class="bi bi-arrow-left text-lg"></i>
                Back
            </a>
            <a href="{{ route('articles.edit', $article) }}" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/20 hover:bg-primary transition-all active:scale-95 flex items-center gap-3">
                <i class="bi bi-pencil-square text-lg"></i>
                Modify Story
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-8 bg-green-500 text-white p-5 rounded-2xl shadow-xl shadow-green-500/20 flex items-center gap-4">
            <i class="bi bi-check-circle-fill text-2xl"></i>
            <span class="font-bold uppercase tracking-widest text-xs">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
        {{-- Left Column: Story Preview --}}
        <div class="xl:col-span-2 space-y-10">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </span>
                        Story Content
                    </h3>
                    <span class="px-4 py-1.5 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded-lg">{{ $article->status }}</span>
                </div>
                
                <div class="p-10 space-y-10">
                    <div class="aspect-video w-full rounded-3xl overflow-hidden shadow-lg border border-slate-100 bg-slate-50">
                        @if($article->image)
                            <img src="{{ asset('storage/' . $article->image) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-200 text-5xl">
                                <i class="bi bi-image"></i>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6">
                        <h2 class="text-3xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic leading-tight">{{ $article->title }}</h2>
                        <div class="prose prose-slate max-w-none prose-lg prose-p:text-slate-600 prose-headings:font-black prose-headings:uppercase prose-headings:italic">
                            {!! $article->content !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comments Management --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden" id="comments">
                <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                            <i class="bi bi-chat-dots"></i>
                        </span>
                        Community Moderation
                    </h3>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $article->comments()->withoutGlobalScope('active_user')->count() }} Total Comments</span>
                </div>

                <div class="p-10 space-y-8">
                    @forelse($article->comments()->withoutGlobalScope('active_user')->latest()->get() as $comment)
                        <div class="flex gap-6 group">
                            <div class="shrink-0">
                                <div class="w-14 h-14 bg-slate-50 border border-slate-100 text-slate-300 rounded-2xl flex items-center justify-center text-xl font-black">
                                    {{ substr($comment->user->name, 0, 1) }}
                                </div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <h5 class="text-xs font-black text-slate-900 uppercase tracking-widest">{{ $comment->user->name }}</h5>
                                        @if($comment->user->status === 'blocked')
                                            <span class="px-2 py-0.5 bg-red-100 text-red-500 text-[8px] font-black uppercase tracking-widest rounded">Blocked User</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">{{ $comment->created_at->format('d M, Y H:i') }}</span>
                                        <form action="{{ route('articles.comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Delete this comment permanentally?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600 transition-colors text-lg">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 relative group-hover:bg-slate-100 transition-all">
                                    <p class="text-slate-600 text-sm leading-relaxed">{{ $comment->comment }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-20 bg-slate-50/50 rounded-3xl border border-slate-50">
                            <i class="bi bi-chat-square-text text-5xl text-slate-200"></i>
                            <p class="mt-4 text-[10px] font-black text-slate-300 uppercase tracking-widest">No comments to moderate yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column: Stats & Meta --}}
        <div class="space-y-10">
            {{-- Meta Card --}}
            <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-10 text-white relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl"></div>
                <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-10 flex items-center gap-3 relative z-10">
                    <i class="bi bi-info-circle-fill text-primary"></i>
                    Insight Metadata
                </h3>

                <div class="space-y-8 relative z-10">
                    <div class="flex items-center justify-between">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Taxonomy</p>
                        <p class="text-xs font-black uppercase tracking-widest text-white italic">{{ $article->category->name }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Created</p>
                        <p class="text-xs font-black uppercase tracking-widest text-white">{{ $article->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Slug</p>
                        <p class="text-xs font-mono text-slate-400">{{ $article->slug }}</p>
                    </div>
                    <div class="pt-8 border-t border-white/10 grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Reactions</p>
                            <p class="text-xl font-black text-primary">{{ $article->reactions()->count() }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Engagement</p>
                            <p class="text-xl font-black text-primary">{{ $article->comments()->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Engagement Chart Placeholder --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-10 text-center">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h6 class="text-[10px] font-black text-slate-900 uppercase tracking-widest mb-2">Popularity Index</h6>
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Story engagement is trending upward compared to last week.</p>
            </div>
        </div>
    </div>
</div>
@endsection
