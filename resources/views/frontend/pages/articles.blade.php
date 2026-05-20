@extends('frontend.layout')

@section('title', 'Tech Blog | Digital Insights')

@section('content')
<section class="py-16 lg:py-24 bg-white min-h-screen">
    <div class="max-w-6xl mx-auto px-4">
        
        <!-- Minimal Header -->
        <div class="mb-20 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-slate-900 tracking-tighter uppercase italic mb-4">Tech Insights</h1>
            <div class="w-20 h-1.5 bg-primary mx-auto rounded-full"></div>
        </div>

        <div class="flex flex-col lg:flex-row gap-16">
            <!-- Sidebar -->
            <aside class="w-full lg:w-1/4 space-y-12">
                <!-- Search -->
                <div class="space-y-4">
                    <h5 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.2em]">Search</h5>
                    <form action="{{ route('frontend.articles') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="w-full border-b-2 border-slate-100 py-3 text-sm font-bold focus:border-primary transition-all placeholder:text-slate-300 outline-none bg-transparent"
                               placeholder="Type keywords...">
                        <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="space-y-6">
                    <h5 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.2em]">Topics</h5>
                    <div class="flex flex-col gap-4">
                        <a href="{{ route('frontend.articles') }}" 
                           class="text-xs font-bold uppercase tracking-widest no-underline transition-colors {{ !request('category') ? 'text-primary' : 'text-slate-400 hover:text-slate-900' }}">
                            All Tech Stories
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ route('frontend.articles', ['category' => $category->id]) }}" 
                               class="text-xs font-bold uppercase tracking-widest no-underline transition-colors {{ request('category') == $category->id ? 'text-primary' : 'text-slate-400 hover:text-slate-900' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <!-- Articles Grid -->
            <div class="w-full lg:w-3/4">
                <div class="space-y-24">
                    @forelse($articles as $article)
                        <article class="group">
                            <div class="flex flex-col md:flex-row gap-8 lg:gap-12 items-center">
                                <div class="w-full md:w-2/5 overflow-hidden rounded-2xl">
                                    <a href="{{ route('frontend.articles.show', $article->slug) }}">
                                        <img src="{{ asset('storage/' . $article->image) }}" alt="{{ $article->title }}" 
                                             class="w-full aspect-[4/3] object-cover transition-transform duration-700 group-hover:scale-110">
                                    </a>
                                </div>
                                <div class="flex-1 space-y-4">
                                    <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">{{ $article->category->name }}</span>
                                    <h3 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tighter uppercase italic leading-tight group-hover:text-primary transition-colors">
                                        <a href="{{ route('frontend.articles.show', $article->slug) }}" class="no-underline text-inherit">
                                            {{ $article->title }}
                                        </a>
                                    </h3>
                                    <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 font-medium">
                                        {{ Str::limit(strip_tags($article->content), 150) }}
                                    </p>
                                    <div class="flex items-center gap-6 text-[10px] font-black text-slate-300 uppercase tracking-widest pt-2">
                                        <span>{{ $article->created_at->format('M d, Y') }}</span>
                                        <span>/</span>
                                        <span>{{ $article->comments_count ?? $article->comments()->count() }} Comments</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="py-20 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No stories found in this section.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($articles->hasPages())
                <div class="mt-24 pt-12 border-t border-slate-100 flex justify-center">
                    {{ $articles->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<style>
    .pagination { display: flex; gap: 0.5rem; }
    .page-item .page-link {
        border: none !important;
        background: transparent !important;
        color: #94a3b8 !important;
        font-weight: 900 !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        padding: 0.5rem 1rem !important;
    }
    .page-item.active .page-link { color: #20a7db !important; }
</style>
@endsection
