@extends('frontend.layout')

@section('title', $article->title)

@section('content')
<article class="py-16 lg:py-24 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Minimal Header -->
        <header class="mb-16">
            <div class="flex items-center gap-4 text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-6">
                <span>{{ $article->category->name }}</span>
                <span class="text-slate-200">•</span>
                <span class="text-slate-400">{{ $article->created_at->format('M d, Y') }}</span>
            </div>
            <h1 class="text-4xl lg:text-6xl font-black text-slate-900 tracking-tighter uppercase italic leading-[1.1] mb-8">{{ $article->title }}</h1>
            <div class="flex items-center gap-3 text-slate-400">
                <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-sm">
                    <i class="bi bi-person"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest italic">ONEMALL Editorial Team</span>
            </div>
        </header>

        <!-- Featured Image -->
        <div class="mb-16 overflow-hidden rounded-2xl shadow-sm border border-slate-50">
            <img src="{{ asset('storage/' . $article->image) }}" class="w-full h-auto object-cover" alt="{{ $article->title }}">
        </div>

        <!-- Content -->
        <div class="prose prose-slate max-w-none prose-lg lg:prose-xl prose-p:text-slate-600 prose-p:leading-relaxed prose-headings:text-slate-900 prose-headings:font-black prose-headings:uppercase prose-headings:italic prose-headings:tracking-tight prose-img:rounded-xl">
            {!! $article->content !!}
        </div>

        <!-- Interactions (Clean) -->
        <div class="mt-20 pt-12 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-8" x-data="reactionHandler()">
            <button @click="toggleLike" 
                    :class="liked ? 'text-primary' : 'text-slate-400 hover:text-primary'"
                    class="flex items-center gap-3 font-black uppercase text-xs tracking-widest transition-all outline-none">
                <i class="bi text-xl" :class="liked ? 'bi-heart-fill' : 'bi-heart'"></i>
                <span x-text="count + ' Likes'"></span>
            </button>

            <div class="flex gap-4">
                <a href="#" class="text-slate-300 hover:text-slate-900 transition-colors"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-slate-300 hover:text-slate-900 transition-colors"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="text-slate-300 hover:text-slate-900 transition-colors"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>

        <!-- Comments (Simplified) -->
        <section class="mt-32 space-y-16" id="comments">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter italic">Discussion ({{ $article->comments->count() }})</h3>
            </div>

            @if(session('success'))
                <div class="p-4 bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            @auth
                <form action="{{ route('frontend.articles.comment', $article->id) }}" method="POST" class="space-y-6">
                    @csrf
                    <textarea name="comment" rows="4" required
                              class="w-full bg-slate-50 border-0 rounded-xl px-6 py-5 text-sm font-medium focus:ring-1 focus:ring-primary/20 transition-all placeholder:text-slate-300"
                              placeholder="Add a comment..."></textarea>
                    <button type="submit" class="px-8 py-4 bg-slate-900 text-white font-black uppercase text-[10px] tracking-widest rounded-lg hover:bg-primary transition-all">
                        Post Comment
                    </button>
                </form>
            @else
                <div class="p-10 bg-slate-50 rounded-2xl text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Please login to join the discussion</p>
                    <a href="{{ route('user.auth.login') }}" class="text-xs font-black text-primary uppercase tracking-widest no-underline border-b-2 border-primary/20 hover:border-primary">Login Now</a>
                </div>
            @endauth

            <div class="space-y-12">
                @forelse($article->comments->sortByDesc('created_at') as $comment)
                    <div class="flex gap-6" x-data="{ editing: false }">
                        <div class="w-12 h-12 bg-slate-50 text-slate-300 rounded-lg flex items-center justify-center text-xl font-black shrink-0">
                            {{ substr($comment->user->name, 0, 1) }}
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h5 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">{{ $comment->user->name }}</h5>
                                    @if(auth()->id() === $comment->user_id)
                                        <span class="text-[8px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-400 font-black uppercase tracking-widest">You</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">{{ $comment->created_at->diffForHumans() }}</span>
                                    
                                    @if(auth()->id() === $comment->user_id)
                                        <div class="flex items-center gap-4">
                                            {{-- Edit Trigger --}}
                                            <button @click="editing = !editing" type="button" class="text-slate-400 hover:text-primary transition-colors cursor-pointer outline-none border-none bg-transparent p-0">
                                                <i class="bi bi-pencil text-sm"></i>
                                            </button>
                                            
                                            {{-- Delete Form (Alpine Native) --}}
                                            <form x-ref="deleteForm" action="{{ route('frontend.articles.comment.delete', $comment->id) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button @click="if(confirm('Delete this comment permanently?')) $refs.deleteForm.submit()" type="button" class="text-slate-400 hover:text-red-500 transition-colors cursor-pointer outline-none border-none bg-transparent p-0">
                                                <i class="bi bi-trash text-sm"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Display Text --}}
                            <div :class="editing ? 'hidden' : ''">
                                <p class="text-slate-500 text-sm leading-relaxed">
                                    {{ $comment->comment }}
                                </p>
                            </div>

                            {{-- Edit Form --}}
                            <div x-show="editing" x-cloak>
                                <form action="{{ route('frontend.articles.comment.update', $comment->id) }}" method="POST" class="space-y-3">
                                    @csrf 
                                    @method('PUT')
                                    <textarea name="comment" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium focus:ring-1 focus:ring-primary/20 transition-all outline-none" rows="3">{{ $comment->comment }}</textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-primary transition-all">Save Changes</button>
                                        <button type="button" @click="editing = false" class="px-4 py-2 bg-slate-100 text-slate-400 text-[9px] font-black uppercase tracking-widest rounded-lg">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-[10px] font-black text-slate-300 uppercase tracking-widest">No comments yet.</p>
                @endforelse
            </div>
        </section>

        <!-- More Articles (Minimal Grid) -->
        <section class="mt-32 pt-24 border-t border-slate-100">
            <h5 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-12 italic">More Stories</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                @foreach($otherArticles as $other)
                    <a href="{{ route('frontend.articles.show', $other->slug) }}" class="group no-underline block space-y-4">
                        <div class="aspect-video overflow-hidden rounded-xl bg-slate-50">
                            <img src="{{ asset('storage/' . $other->image) }}" class="w-full h-full object-cover transition-transform group-hover:scale-105" alt="{{ $other->title }}">
                        </div>
                        <h6 class="text-sm font-black text-slate-900 group-hover:text-primary transition-colors uppercase italic tracking-tight leading-tight">{{ $other->title }}</h6>
                    </a>
                @endforeach
            </div>
        </section>

    </div>
</article>

<script>
function reactionHandler() {
    return {
        liked: @json($article->isLikedBy(auth()->user())),
        count: @json($article->reactions->count()),
        async toggleLike() {
            if (!@json(auth()->check())) {
                window.location.href = "{{ route('user.auth.login') }}";
                return;
            }
            try {
                const response = await fetch("{{ route('frontend.articles.react', $article->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.liked = data.liked;
                    this.count = data.count;
                }
            } catch (error) {
                console.error('Error toggling reaction:', error);
            }
        }
    }
}
</script>
@endsection
