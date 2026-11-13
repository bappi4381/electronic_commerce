@extends('admin.layouts')
@section('title', $article->title)
@section('ui-v2', '1')

@section('content')
@php
    $comments = $article->comments()->withoutGlobalScope('active_user')->with('user')->latest()->get();
@endphp

<x-admin.page-header :title="$article->title" :description="'Created ' . $article->created_at->format('d M Y')"
                     :breadcrumbs="['Tech blog' => route('articles.index'), \Illuminate\Support\Str::limit($article->title, 40) => null]">
    <x-admin.button variant="secondary" :href="route('frontend.articles.show', $article->slug)" target="_blank" rel="noopener" icon="bi-box-arrow-up-right">View on site</x-admin.button>
    @can('articles.edit')
        <x-admin.button :href="route('articles.edit', $article)" icon="bi-pencil">Edit article</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="min-w-0 space-y-6 xl:col-span-2">
        <x-admin.card :padding="false">
            @if($article->image)
                <img src="{{ asset('storage/' . $article->image) }}" alt="" class="aspect-video w-full rounded-t-xl object-cover">
            @endif
            <div class="prose prose-gray max-w-none p-6 dark:prose-invert lg:p-8">
                {!! clean($article->content) !!}
            </div>
        </x-admin.card>

        <x-admin.card title="Comments" :description="$comments->count() . ' ' . \Illuminate\Support\Str::plural('comment', $comments->count())" :padding="false" id="comments">
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($comments as $comment)
                    <li class="flex gap-4 px-6 py-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $comment->user?->name ?? 'Deleted user' }}</p>
                                @if($comment->user?->status === 'blocked')
                                    <x-admin.status-badge status="blocked" label="Blocked user" />
                                @endif
                                <time class="text-xs text-gray-500 dark:text-gray-400" datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d M Y, H:i') }}</time>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $comment->comment }}</p>
                        </div>
                        @can('articles.delete')
                            <form method="POST" action="{{ route('articles.comments.destroy', $comment->id) }}" class="shrink-0"
                                  data-confirm="The comment is removed permanently." data-confirm-title="Delete this comment?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" aria-label="Delete comment by {{ $comment->user?->name }}" />
                            </form>
                        @endcan
                    </li>
                @empty
                    <li><x-admin.empty-state title="No comments yet" description="Reader comments on this article appear here." icon="bi-chat-square-text" /></li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>

    <x-admin.card title="Details" class="self-start">
        <dl class="divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <div class="flex justify-between gap-4 pb-3"><dt class="text-gray-500 dark:text-gray-400">Status</dt><dd><x-admin.status-badge :status="$article->status ?? 'draft'" /></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500 dark:text-gray-400">Category</dt><dd class="font-medium">{{ $article->category->name ?? 'Uncategorized' }}</dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500 dark:text-gray-400">Published</dt><dd class="font-medium">{{ optional($article->published_at)->format('d M Y') ?? '—' }}</dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500 dark:text-gray-400">Slug</dt><dd class="truncate font-mono text-xs">{{ $article->slug }}</dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500 dark:text-gray-400">Reactions</dt><dd class="font-medium tabular-nums">{{ $article->reactions()->count() }}</dd></div>
            <div class="flex justify-between gap-4 pt-3"><dt class="text-gray-500 dark:text-gray-400">Comments</dt><dd class="font-medium tabular-nums">{{ $comments->count() }}</dd></div>
        </dl>
    </x-admin.card>
</div>
@endsection
