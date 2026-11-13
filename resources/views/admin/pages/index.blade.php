@extends('admin.layouts')
@section('title', 'Pages')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Pages" description="Static pages like About, FAQ and policies. Published pages are linked in the storefront footer.">
    @can('pages.create')
        <x-admin.button :href="route('admin.pages.create')" icon="bi-plus-lg">New page</x-admin.button>
    @endcan
</x-admin.page-header>

<x-admin.data-table
    :columns="[
        ['label' => 'Page', 'sortable' => true],
        ['label' => 'URL'],
        ['label' => 'Status'],
        ['label' => 'Updated', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No pages yet" empty-icon="bi-file-earmark-text">
    @foreach($pages as $page)
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $page->getTranslation('title', 'en') }}">
                <div class="min-w-0">
                    @can('pages.edit')
                        <a href="{{ route('admin.pages.edit', $page) }}" class="block truncate rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">{{ $page->getTranslation('title', 'en') }}</a>
                    @else
                        <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $page->getTranslation('title', 'en') }}</span>
                    @endcan
                    <span class="block truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $page->getTranslation('title', 'bn', false) ?: 'No Bangla title' }}</span>
                </div>
            </td>
            <td data-label="URL" class="whitespace-nowrap">
                <span class="font-mono text-xs text-gray-600 dark:text-gray-300">/page/{{ $page->slug }}</span>
                @if($page->isCore())<x-admin.status-badge tone="yellow" :dot="false" label="Core" class="ml-1" />@endif
            </td>
            <td data-label="Status"><x-admin.status-badge :status="$page->is_published ? 'published' : 'draft'" /></td>
            <td data-label="Updated" data-sort="{{ $page->updated_at?->timestamp }}" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">{{ $page->updated_at?->diffForHumans() }}</td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    @if($page->is_published)
                        <x-admin.button variant="ghost" size="sm" :href="route('pages.show', ['locale' => 'en', 'slug' => $page->slug])" target="_blank" rel="noopener" icon="bi-box-arrow-up-right" title="View on site" aria-label="View {{ $page->getTranslation('title', 'en') }} on the site" />
                    @endif
                    @can('pages.edit')
                        <x-admin.button variant="ghost" size="sm" :href="route('admin.pages.edit', $page)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $page->getTranslation('title', 'en') }}" />
                    @endcan
                    @can('pages.delete')
                        @unless($page->isCore())
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                                  data-confirm="The page and its URL are removed from the storefront." data-confirm-title="Delete {{ $page->getTranslation('title', 'en') }}?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $page->getTranslation('title', 'en') }}" />
                            </form>
                        @endunless
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
