@extends('admin.layouts')
@section('title', 'Tech blog')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Tech blog" description="Articles, their engagement and publishing status.">
    @can('articles.create')
        <x-admin.button :href="route('articles.create')" icon="bi-plus-lg">Write article</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.stat-card label="Articles" :value="number_format(\App\Models\Article::count())" icon="bi-journal-text" />
    <x-admin.stat-card label="Published" :value="number_format(\App\Models\Article::where('status', 'published')->count())" icon="bi-send-check" />
    <x-admin.stat-card label="Reader reactions" :value="number_format(\App\Models\BlogReaction::count())" icon="bi-heart" />
</div>

<x-admin.data-table :paginator="$articles" search="search" search-placeholder="Search articles…"
    :columns="[
        ['label' => 'Article', 'sortable' => true],
        ['label' => 'Category', 'sortable' => true],
        ['label' => 'Engagement', 'align' => 'center'],
        ['label' => 'Status'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No articles yet" empty-description="Share tech news, guides and reviews with your customers." empty-icon="bi-journal-x">
    @foreach($articles as $article)
        @php($status = $article->status ?? 'draft')
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $article->title }}" class="md:w-full md:max-w-0">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                        @if($article->image)
                            <img src="{{ asset('storage/' . $article->image) }}" alt="" class="size-full object-cover" loading="lazy">
                        @else
                            <i class="bi bi-image text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <a href="{{ route('articles.show', $article) }}" class="block truncate rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">{{ $article->title }}</a>
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $article->published_at ? 'Published ' . $article->published_at->format('d M Y') : 'No publish date' }}</span>
                    </div>
                </div>
            </td>
            <td data-label="Category" class="whitespace-nowrap">{{ $article->category->name ?? 'Uncategorized' }}</td>
            <td data-label="Engagement" class="whitespace-nowrap text-center">
                <span class="inline-flex items-center gap-3 text-sm text-gray-600 tabular-nums dark:text-gray-300">
                    <span title="Reactions"><i class="bi bi-heart-fill text-xs text-red-400" aria-hidden="true"></i> {{ $article->reactions()->count() }}<span class="sr-only"> reactions</span></span>
                    <span title="Comments"><i class="bi bi-chat-fill text-xs text-blue-400" aria-hidden="true"></i> {{ $article->comments()->count() }}<span class="sr-only"> comments</span></span>
                </span>
            </td>
            <td data-label="Status"><x-admin.status-badge :status="$status" /></td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    <x-admin.button variant="ghost" size="sm" :href="route('frontend.articles.show', $article->slug)" target="_blank" rel="noopener" icon="bi-box-arrow-up-right" title="View on site" aria-label="View {{ $article->title }} on the site" />
                    @can('articles.edit')
                        <x-admin.button variant="ghost" size="sm" :href="route('articles.edit', $article)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $article->title }}" />
                    @endcan
                    @can('articles.delete')
                        <form method="POST" action="{{ route('articles.destroy', $article) }}"
                              data-confirm="The article is removed from the blog." data-confirm-title="Delete “{{ \Illuminate\Support\Str::limit($article->title, 40) }}”?" data-confirm-button="Delete">
                            @csrf @method('DELETE')
                            <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $article->title }}" />
                        </form>
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
