@extends('admin.layouts')
@section('title', 'Permissions')
@section('ui-v2', '1')

@section('content')
@php
    $total = $permissions->total();
    $corePerms = \App\Support\AdminPermissions::all();
@endphp

<x-admin.page-header title="Permissions" description="Access keys on the admin guard. Core permissions are built in and protected."
                     :breadcrumbs="['Roles' => route('roles.index'), 'Permissions' => null]">
    @can('roles.create')
        <x-admin.button :href="route('permissions.create')" icon="bi-plus-lg">New permission</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-admin.stat-card label="Total" :value="number_format($total)" icon="bi-key" />
    <x-admin.stat-card label="Core" :value="number_format(count($corePerms))" icon="bi-shield-lock" />
    <x-admin.stat-card label="Custom" :value="number_format(max(0, $total - count($corePerms)))" icon="bi-sliders" />
</div>

<x-admin.data-table :paginator="$permissions" search="search" search-placeholder="Search permissions…"
    :columns="[
        ['label' => 'Permission', 'sortable' => true],
        ['label' => 'Type'],
        ['label' => 'Roles', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No permissions defined" empty-icon="bi-key">
    @foreach($permissions as $permission)
        @php($isCore = in_array($permission->name, $corePerms, true))
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $permission->name }}">
                <div class="min-w-0">
                    <code class="block truncate font-mono text-sm font-medium text-gray-900 dark:text-white">{{ $permission->name }}</code>
                    <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">Guard: {{ $permission->guard_name }}</span>
                </div>
            </td>
            <td data-label="Type">
                @if($isCore)
                    <x-admin.status-badge tone="yellow" :dot="false" label="Core" />
                @else
                    <x-admin.status-badge tone="gray" :dot="false" label="Custom" />
                @endif
            </td>
            <td data-label="Roles" data-sort="{{ $permission->roles_count }}" class="text-right tabular-nums">{{ $permission->roles_count }}</td>
            <td class="whitespace-nowrap text-right">
                @if($isCore)
                    <x-admin.button variant="ghost" size="sm" :href="route('permissions.edit', $permission)" icon="bi-eye" title="View roles" aria-label="View {{ $permission->name }}" />
                @else
                    <div class="inline-flex items-center gap-1">
                        @can('roles.edit')
                            <x-admin.button variant="ghost" size="sm" :href="route('permissions.edit', $permission)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $permission->name }}" />
                        @endcan
                        @can('roles.delete')
                            <form method="POST" action="{{ route('permissions.destroy', $permission) }}"
                                  data-confirm="It's removed from every role that has it. This can't be undone." data-confirm-title="Delete {{ $permission->name }}?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $permission->name }}" />
                            </form>
                        @endcan
                    </div>
                @endif
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
