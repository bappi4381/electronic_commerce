@extends('admin.layouts')
@section('title', 'Roles')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Roles" description="Access levels for admin users. Super Admin always has every permission.">
    @can('roles.view')
        <x-admin.button variant="secondary" :href="route('admins.index')" icon="bi-person-badge">Admin users</x-admin.button>
    @endcan
    @can('roles.create')
        <x-admin.button :href="route('roles.create')" icon="bi-plus-lg">New role</x-admin.button>
    @endcan
</x-admin.page-header>

<x-admin.data-table :paginator="$roles" search="search" search-placeholder="Search roles…"
    :columns="[
        ['label' => 'Role', 'sortable' => true],
        ['label' => 'Permissions'],
        ['label' => 'Count', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No roles yet" empty-description="Create a role, then assign it to admin users." empty-icon="bi-shield-lock">
    @foreach($roles as $role)
        @php($isSuper = $role->name === 'Super Admin')
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $role->name }}">
                <div class="flex items-center gap-3">
                    <span @class(['flex size-9 shrink-0 items-center justify-center rounded-lg', 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' => $isSuper, 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' => !$isSuper]) aria-hidden="true">
                        <i class="bi {{ $isSuper ? 'bi-shield-fill-check' : 'bi-shield-lock' }}"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">Guard: {{ $role->guard_name }}</span>
                    </div>
                </div>
            </td>
            <td data-label="Permissions" class="md:max-w-xl">
                @if($isSuper)
                    <x-admin.status-badge tone="brand" label="All permissions" />
                @else
                    <div class="flex flex-wrap justify-end gap-1 md:justify-start">
                        @forelse($role->permissions->take(8) as $permission)
                            <x-admin.status-badge tone="gray" :dot="false" :label="str_replace(['-', '.'], [' ', ' · '], $permission->name)" />
                        @empty
                            <span class="text-sm text-gray-400 dark:text-gray-500">None</span>
                        @endforelse
                        @if($role->permissions->count() > 8)
                            <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $role->permissions->count() - 8 }} more</span>
                        @endif
                    </div>
                @endif
            </td>
            <td data-label="Count" data-sort="{{ $isSuper ? PHP_INT_MAX : $role->permissions_count }}" class="text-right tabular-nums">{{ $isSuper ? 'All' : $role->permissions_count }}</td>
            <td class="whitespace-nowrap text-right">
                @if($isSuper)
                    <span class="text-xs text-gray-400 dark:text-gray-500">System role</span>
                @else
                    <div class="inline-flex items-center gap-1">
                        @can('roles.edit')
                            <x-admin.button variant="ghost" size="sm" :href="route('roles.edit', $role)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $role->name }}" />
                        @endcan
                        @can('roles.delete')
                            <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                  data-confirm="Admins with this role lose its permissions. This can't be undone." data-confirm-title="Delete the {{ $role->name }} role?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $role->name }}" />
                            </form>
                        @endcan
                    </div>
                @endif
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
