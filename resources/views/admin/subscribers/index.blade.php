@extends('admin.layouts')
@section('title', 'Subscribers')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Newsletter subscribers" description="People who signed up for emails in the storefront footer."
                     :breadcrumbs="['Marketing' => route('marketing.index'), 'Subscribers' => null]">
    <x-admin.button variant="secondary" :href="route('admin.subscribers.export', array_filter($filters))" icon="bi-filetype-csv">Export CSV</x-admin.button>
</x-admin.page-header>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.stat-card label="Active subscribers" :value="number_format($counts['active'])" icon="bi-envelope-check" />
    <x-admin.stat-card label="All time" :value="number_format($counts['total'])" icon="bi-people" />
    <x-admin.stat-card label="Joined in the last 30 days" :value="number_format($counts['last30'])" icon="bi-graph-up-arrow" />
</div>

<x-admin.data-table :paginator="$subscribers" search="search" search-placeholder="Search email…"
    :columns="[
        ['label' => 'Email', 'sortable' => true],
        ['label' => 'Status'],
        ['label' => 'Subscribed', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No subscribers yet" empty-description="Newsletter sign-ups appear here." empty-icon="bi-envelope-paper">
    <x-slot:filters>
        <label for="filter-status" class="sr-only">Status</label>
        <select id="filter-status" name="status" onchange="this.form.requestSubmit()"
                class="h-10 rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <option value="">All statuses</option>
            <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
            <option value="unsubscribed" @selected(($filters['status'] ?? null) === 'unsubscribed')>Unsubscribed</option>
        </select>
    </x-slot:filters>

    @foreach($subscribers as $subscriber)
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $subscriber->email }}" class="font-medium text-gray-900 dark:text-white">
                <span class="block truncate">{{ $subscriber->email }}</span>
            </td>
            <td data-label="Status"><x-admin.status-badge :status="$subscriber->status ? 'active' : 'unsubscribed'" /></td>
            <td data-label="Subscribed" data-sort="{{ $subscriber->created_at?->timestamp }}" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">{{ $subscriber->created_at?->format('d M Y') }}</td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    @can('marketing.edit')
                        <form method="POST" action="{{ route('admin.subscribers.toggle', $subscriber) }}">
                            @csrf @method('PATCH')
                            <x-admin.button type="submit" variant="secondary" size="sm">{{ $subscriber->status ? 'Unsubscribe' : 'Re-subscribe' }}</x-admin.button>
                        </form>
                    @endcan
                    @can('marketing.delete')
                        <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}"
                              data-confirm="The address is removed from the list permanently." data-confirm-title="Remove {{ $subscriber->email }}?" data-confirm-button="Remove">
                            @csrf @method('DELETE')
                            <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" aria-label="Remove {{ $subscriber->email }}" />
                        </form>
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
