@extends('admin.layouts')
@section('title', 'Reviews · ' . $product->getTranslation('name', 'en'))
@section('ui-v2', '1')

@section('content')
@php
    $name = $product->getTranslation('name', 'en');
    $image = $product->images->first();
    $comments = $product->comments->sortByDesc('created_at');
    $byStatus = $comments->countBy('status');
@endphp

<x-admin.page-header :title="$name" description="Customer reviews and comments for this product."
                     :breadcrumbs="['Products' => route('admin.products.index'), 'Reviews' => null]">
    @can('reviews.view')
        <x-admin.button variant="secondary" :href="route('admin.reviews.index', ['search' => $name])" icon="bi-star-half">Open moderation queue</x-admin.button>
    @endcan
    @can('products.edit')
        <x-admin.button :href="route('admin.products.edit', $product)" icon="bi-pencil">Edit product</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <x-admin.card :padding="false" class="self-start">
        <div class="aspect-square overflow-hidden rounded-t-xl bg-gray-100 dark:bg-gray-800">
            @if($image)
                <img src="{{ asset('storage/' . $image->image) }}" alt="{{ $name }}" class="size-full object-contain p-4">
            @else
                <div class="flex size-full items-center justify-center text-5xl text-gray-300 dark:text-gray-600"><i class="bi bi-image" aria-hidden="true"></i></div>
            @endif
        </div>
        <dl class="divide-y divide-gray-200 px-6 text-sm dark:divide-gray-800">
            <div class="flex justify-between py-3"><dt class="text-gray-500 dark:text-gray-400">Price</dt><dd class="font-medium tabular-nums">Tk {{ number_format($product->discounted_price ?: $product->price, 2) }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500 dark:text-gray-400">Average rating</dt><dd class="font-medium">{{ $product->rating_count ? number_format($product->rating_avg, 1) . ' ★' : '—' }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500 dark:text-gray-400">Published reviews</dt><dd class="font-medium tabular-nums">{{ $byStatus['approved'] ?? 0 }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500 dark:text-gray-400">Awaiting approval</dt><dd class="font-medium tabular-nums">{{ $byStatus['pending'] ?? 0 }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500 dark:text-gray-400">Reactions</dt><dd class="font-medium tabular-nums">{{ $product->reactions->count() }}</dd></div>
        </dl>
    </x-admin.card>

    <x-admin.card title="Reviews" :description="$comments->count() . ' ' . \Illuminate\Support\Str::plural('review', $comments->count())" :padding="false" class="lg:col-span-2">
        <ul class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($comments as $comment)
                <li class="flex gap-4 px-6 py-5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $comment->user?->name ?? 'Deleted user' }}</p>
                            @if($comment->rating)
                                <span class="text-sm text-yellow-500" aria-label="{{ $comment->rating }} out of 5 stars">{{ str_repeat('★', $comment->rating) }}<span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', 5 - $comment->rating) }}</span></span>
                            @endif
                            <x-admin.status-badge :status="$comment->status" />
                            @if($comment->user && $comment->user->status !== 'active')
                                <x-admin.status-badge status="blocked" label="Blocked user" />
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->format('d M Y, H:i') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $comment->comment }}</p>
                    </div>
                    @can('products.delete')
                        <form method="POST" action="{{ route('admin.products.comment.destroy', $comment->id) }}" class="shrink-0"
                              data-confirm="The review is permanently removed and the product rating is recalculated." data-confirm-title="Delete this review?" data-confirm-button="Delete">
                            @csrf @method('DELETE')
                            <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" aria-label="Delete review by {{ $comment->user?->name }}" />
                        </form>
                    @endcan
                </li>
            @empty
                <li><x-admin.empty-state title="No reviews yet" description="Reviews customers leave on this product will appear here." icon="bi-chat-left-text" /></li>
            @endforelse
        </ul>
    </x-admin.card>
</div>
@endsection
