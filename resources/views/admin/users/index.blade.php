@extends('admin.layouts')
@section('title', 'Customers')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Customers" description="Customer accounts, contact details and access.">
    @can('users.create')
        <x-admin.button :href="route('users.create')" icon="bi-person-plus">Add customer</x-admin.button>
    @endcan
</x-admin.page-header>

<x-admin.data-table :paginator="$users" search="search" search-placeholder="Search name, email or phone…"
    :columns="[
        ['label' => 'Customer', 'sortable' => true],
        ['label' => 'ID', 'sortable' => true],
        ['label' => 'Location', 'sortable' => true],
        ['label' => 'Status'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No customers yet" empty-description="Customers who register or order appear here." empty-icon="bi-people">
    @foreach($users as $user)
        @php($isActive = $user->status === 'active')
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $user->name }}" class="md:w-full md:max-w-0">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <a href="{{ route('users.show', $user) }}" class="block truncate rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">{{ $user->name }}</a>
                        <span class="block truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</span>
                    </div>
                </div>
            </td>
            <td data-label="ID" class="whitespace-nowrap font-mono text-xs text-gray-500 dark:text-gray-400">#{{ $user->user_id }}</td>
            <td data-label="Location" data-sort="{{ $user->city }}" class="whitespace-nowrap">
                {{ collect([$user->city, $user->country])->filter()->implode(', ') ?: '—' }}
            </td>
            <td data-label="Status"><x-admin.status-badge :status="$isActive ? 'active' : 'blocked'" /></td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    <x-admin.button variant="ghost" size="sm" :href="route('users.show', $user)" icon="bi-eye" title="View" aria-label="View {{ $user->name }}" />
                    @can('users.edit')
                        <x-admin.button variant="ghost" size="sm" :href="route('users.edit', $user)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $user->name }}" />
                        <form method="POST" action="{{ route('users.toggleStatus', $user) }}"
                              data-confirm="{{ $isActive ? 'They will be signed out and can no longer log in or place orders.' : 'They will be able to log in and order again.' }}"
                              data-confirm-title="{{ $isActive ? 'Block' : 'Unblock' }} {{ $user->name }}?" data-confirm-button="{{ $isActive ? 'Block' : 'Unblock' }}">
                            @csrf @method('PATCH')
                            <x-admin.button type="submit" variant="ghost" size="sm" :icon="$isActive ? 'bi-lock' : 'bi-unlock'" :loading="false"
                                            :title="$isActive ? 'Block access' : 'Restore access'" aria-label="{{ $isActive ? 'Block' : 'Unblock' }} {{ $user->name }}" />
                        </form>
                    @endcan
                    @can('users.delete')
                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              data-confirm="Customers with orders cannot be deleted; block them instead." data-confirm-title="Delete {{ $user->name }}?" data-confirm-button="Delete">
                            @csrf @method('DELETE')
                            <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $user->name }}" />
                        </form>
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
