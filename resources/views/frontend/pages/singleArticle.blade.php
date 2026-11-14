@extends('frontend.layout')

@php
    $storeName = \App\Models\Setting::get('site_name', 'ONEMALL');
    $url = route('frontend.articles.show', $article->slug);
    $image = $article->image ? asset('storage/' . $article->image) : null;
    $published = $article->published_at ?? $article->created_at;
    $comments = $article->comments->sortByDesc('created_at');
    $shareText = urlencode($article->title . ' ' . $url);
@endphp

@section('title', $article->title)
@section('meta_description', $article->excerpt(155))

@push('head')
    <link rel="canonical" href="{{ $url }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->title }}">
    <meta property="og:description" content="{{ $article->excerpt(155) }}">
    <meta property="og:url" content="{{ $url }}">
    @if($image)<meta property="og:image" content="{{ $image }}">@endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $article->title,
        'description' => $article->excerpt(155),
        'image' => $image,
        'datePublished' => $published->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $storeName],
        'publisher' => ['@type' => 'Organization', 'name' => $storeName],
        'mainEntityOfPage' => $url,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-12 pt-4 md:pb-16 md:pt-6">
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex min-w-0 items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li><a href="{{ route('frontend.articles') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Tech blog') }}</a></li>
            @if($article->category)
                <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
                <li class="truncate"><a href="{{ route('frontend.articles', ['category' => $article->category_id]) }}" class="rounded hover:text-brand-700 sf-focus">{{ $article->category->name }}</a></li>
            @endif
        </ol>
    </nav>

    <article class="mx-auto mt-5 max-w-3xl md:mt-8">
        <header>
            @if($article->category)
                <a href="{{ route('frontend.articles', ['category' => $article->category_id]) }}" class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100 sf-focus">{{ $article->category->name }}</a>
            @endif
            <h1 class="mt-3 font-display text-2xl font-bold leading-tight tracking-tight text-slate-900 md:text-4xl">{{ $article->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--sf-border)] pb-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white" aria-hidden="true">{{ mb_substr($storeName, 0, 1) }}</span>
                    <div class="text-sm">
                        <p class="font-semibold text-slate-900">{{ __(':store editorial team', ['store' => $storeName]) }}</p>
                        <p class="text-xs text-slate-500">
                            <time datetime="{{ $published->toDateString() }}">{{ $published->format('d M Y') }}</time>
                            · {{ __(':count min read', ['count' => localized_number($article->readingMinutes())]) }}
                        </p>
                    </div>
                </div>
                @include('frontend.pages.partials.article-share', ['url' => $url, 'shareText' => $shareText])
            </div>
        </header>

        @if($image)
            <figure class="mt-6 overflow-hidden rounded-[var(--sf-radius-lg)] bg-slate-100">
                <img src="{{ $image }}" alt="{{ $article->title }}" class="w-full object-cover">
            </figure>
        @endif

        <div class="sf-article mt-8">
            {!! clean($article->content) !!}
        </div>

        {{-- Like + share --}}
        <div class="mt-10 flex flex-wrap items-center justify-between gap-4 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-4 md:p-5"
             x-data="articleLike({ liked: @js($article->isLikedBy(auth()->user())), count: @js($article->reactions->count()) })">
            <div class="flex items-center gap-3">
                <button type="button" @click="toggle" :aria-pressed="liked.toString()" :disabled="busy"
                        class="flex h-11 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition-colors sf-focus"
                        :class="liked ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-300 text-slate-700 hover:border-rose-300 hover:text-rose-600'">
                    <i class="bi" :class="liked ? 'bi-heart-fill' : 'bi-heart'" aria-hidden="true"></i>
                    <span x-text="liked ? @js(__('Liked')) : @js(__('Helpful? Like it'))">{{ __('Helpful? Like it') }}</span>
                </button>
                <span class="text-sm text-slate-500" x-show="count > 0" x-cloak><span x-text="count"></span> {{ __('likes') }}</span>
            </div>
            @include('frontend.pages.partials.article-share', ['url' => $url, 'shareText' => $shareText])
        </div>

        {{-- Comments --}}
        <section id="comments" class="mt-12 scroll-mt-28" aria-labelledby="comments-title">
            <h2 id="comments-title" class="font-display text-xl font-bold text-slate-900">
                {{ __('Comments') }} <span class="text-base font-medium text-slate-400">({{ localized_number($comments->count()) }})</span>
            </h2>

            @if(session('success'))
                <div class="mt-4 flex gap-3 rounded-[var(--sf-radius-sm)] border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800" role="status">
                    <i class="bi bi-check-circle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('success') }}</p>
                </div>
            @endif

            @auth
                <form action="{{ route('frontend.articles.comment', $article->id) }}" method="POST" class="mt-5 flex gap-3" x-data="{ text: '' }">
                    @csrf
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="flex-1">
                        <label for="new-comment" class="sr-only">{{ __('Add a comment') }}</label>
                        <textarea id="new-comment" name="comment" rows="3" required maxlength="1000" x-model="text" placeholder="{{ __('Share your thoughts or ask a question…') }}"
                                  class="block w-full rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25">{{ old('comment') }}</textarea>
                        @error('comment')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-2 flex justify-end">
                            <button type="submit" :disabled="!text.trim()" class="flex h-10 items-center rounded-[var(--sf-radius-sm)] bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50 sf-focus">{{ __('Post comment') }}</button>
                        </div>
                    </div>
                </form>
            @else
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-[var(--sf-radius-lg)] bg-slate-50 p-4 text-sm">
                    <p class="text-slate-600">{{ __('Log in to join the conversation.') }}</p>
                    <a href="{{ route('user.auth.login', ['redirect' => $url . '#comments']) }}" class="flex h-10 items-center rounded-[var(--sf-radius-sm)] bg-slate-900 px-4 font-semibold text-white hover:bg-slate-800 sf-focus">{{ __('Log in') }}</a>
                </div>
            @endauth

            <ul class="mt-6 divide-y divide-[var(--sf-border)]">
                @forelse($comments as $comment)
                    <li class="flex gap-3 py-5" x-data="{ editing: false }">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600" aria-hidden="true">{{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm">
                                    <span class="font-semibold text-slate-900">{{ $comment->user?->name ?? __('Customer') }}</span>
                                    @if(auth()->id() === $comment->user_id)<span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500">{{ __('You') }}</span>@endif
                                    <span class="ml-1 text-xs text-slate-400">· {{ $comment->created_at->diffForHumans() }}</span>
                                </p>
                                @if(auth()->id() === $comment->user_id)
                                    <div class="flex items-center gap-1" x-show="!editing">
                                        <button type="button" @click="editing = true" class="flex size-9 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-brand-700 sf-focus" aria-label="{{ __('Edit comment') }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                        <form action="{{ route('frontend.articles.comment.delete', $comment->id) }}" method="POST" x-data @submit="if (!confirm(@js(__('Delete this comment?')))) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="flex size-9 items-center justify-center rounded-full text-slate-400 hover:bg-rose-50 hover:text-rose-600 sf-focus" aria-label="{{ __('Delete comment') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-slate-700" x-show="!editing">{{ $comment->comment }}</p>
                            @if(auth()->id() === $comment->user_id)
                                <form x-show="editing" x-cloak action="{{ route('frontend.articles.comment.update', $comment->id) }}" method="POST" class="mt-2 space-y-2">
                                    @csrf @method('PUT')
                                    <label for="edit-comment-{{ $comment->id }}" class="sr-only">{{ __('Edit comment') }}</label>
                                    <textarea id="edit-comment-{{ $comment->id }}" name="comment" rows="3" required maxlength="1000"
                                              class="block w-full rounded-[var(--sf-radius-sm)] border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25">{{ $comment->comment }}</textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex h-9 items-center rounded-[var(--sf-radius-sm)] bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">{{ __('Save') }}</button>
                                        <button type="button" @click="editing = false" class="flex h-9 items-center rounded-[var(--sf-radius-sm)] border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 sf-focus">{{ __('Cancel') }}</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">{{ __('No comments yet. Be the first to share your thoughts.') }}</li>
                @endforelse
            </ul>
        </section>
    </article>

    {{-- Related --}}
    @if($otherArticles->isNotEmpty())
        <section class="mt-14 border-t border-[var(--sf-border)] pt-10" aria-labelledby="related-title">
            <div class="flex items-end justify-between gap-4">
                <h2 id="related-title" class="font-display text-xl font-bold tracking-tight text-slate-900 md:text-2xl">{{ __('Keep reading') }}</h2>
                <a href="{{ route('frontend.articles') }}" class="flex min-h-10 items-center gap-1 rounded text-sm font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">{{ __('All articles') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($otherArticles as $other)
                    <article class="group relative flex flex-col overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white transition-shadow hover:shadow-[var(--sf-shadow)]">
                        <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                            @if($other->image)
                                <img src="{{ asset('storage/' . $other->image) }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-50 to-slate-100 text-4xl text-brand-300" aria-hidden="true"><i class="bi bi-journal-richtext"></i></div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-xs font-semibold text-[var(--sf-accent-strong)]">{{ $other->category?->name }}</p>
                            <h3 class="mt-1.5 line-clamp-2 font-display text-base font-bold leading-snug text-slate-900">
                                <a href="{{ route('frontend.articles.show', $other->slug) }}" class="rounded after:absolute after:inset-0 group-hover:text-brand-700 sf-focus">{{ $other->title }}</a>
                            </h3>
                            <p class="mt-auto pt-3 text-xs text-slate-500">{{ ($other->published_at ?? $other->created_at)->format('d M Y') }} · {{ __(':count min read', ['count' => localized_number($other->readingMinutes())]) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function articleLike(state) {
        return {
            ...state,
            busy: false,
            async toggle() {
                @guest
                    window.location.href = @js(route('user.auth.login', ['redirect' => $url]));
                    return;
                @endguest
                this.busy = true;
                try {
                    const response = await fetch(@js(route('frontend.articles.react', $article->id)), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.liked = data.liked;
                        this.count = data.count;
                    }
                } finally {
                    this.busy = false;
                }
            },
        };
    }
</script>
@endpush
