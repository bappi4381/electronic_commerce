@extends('admin.layouts')
@section('title', 'Admin users')
@section('ui-v2', '1')

@section('content')
@php
    $me = Auth::guard('admin')->id();
@endphp

<x-admin.page-header title="Admin users" description="People who can sign in to this admin panel, and their roles."
                     :breadcrumbs="['Roles' => route('roles.index'), 'Admin users' => null]">
    @can('roles.create')
        <x-admin.button :href="route('admins.create')" icon="bi-person-plus">Add admin</x-admin.button>
    @endcan
</x-admin.page-header>

<x-admin.data-table :paginator="$admins" search="search" search-placeholder="Search name or email…"
    :columns="[
        ['label' => 'Admin', 'sortable' => true],
        ['label' => 'Roles'],
        ['label' => 'Added', 'sortable' => true],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No admins found" empty-icon="bi-person-badge">
    @foreach($admins as $admin)
        @php
            $targetIsSuper = $admin->hasRole(\App\Support\AdminPermissions::SUPER_ADMIN_ROLE);
            $canManage = !$targetIsSuper || $actorIsSuperAdmin;
            $isLastSuper = $targetIsSuper && $superAdminCount <= 1;
            $isMe = $admin->id === $me;
        @endphp
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $admin->name }}" class="md:w-1/3">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-white">
                            <span class="truncate">{{ $admin->name }}</span>
                            @if($isMe)<x-admin.status-badge tone="brand" :dot="false" label="You" />@endif
                        </span>
                        <span class="block truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $admin->email }}</span>
                    </div>
                </div>
            </td>
            <td data-label="Roles">
                <div class="flex flex-wrap justify-end gap-1 md:justify-start">
                    @forelse($admin->roles as $role)
                        <x-admin.status-badge :tone="$role->name === 'Super Admin' ? 'brand' : 'gray'" :dot="false" :label="$role->name" />
                    @empty
                        <span class="text-sm text-gray-400 dark:text-gray-500">No role</span>
                    @endforelse
                </div>
            </td>
            <td data-label="Added" data-sort="{{ $admin->created_at?->timestamp ?? 0 }}" class="whitespace-nowrap text-gray-500 dark:text-gray-400">
                {{ $admin->created_at ? $admin->created_at->format('d M Y') : 'System account' }}
            </td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    @if($canManage)
                        @can('roles.edit')
                            <x-admin.button variant="ghost" size="sm" :href="route('admins.edit', $admin)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $admin->name }}" />
                        @endcan
                    @endif
                    @if($canManage && !$isLastSuper && !$isMe)
                        @can('roles.delete')
                            <form method="POST" action="{{ route('admins.destroy', $admin) }}"
                                  data-confirm="They lose access to the admin panel immediately. This can't be undone." data-confirm-title="Delete {{ $admin->name }}?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $admin->name }}" />
                            </form>
                        @endcan
                    @else
                        <span class="pr-2 text-xs text-gray-400 dark:text-gray-500">{{ $isMe ? 'Your account' : ($isLastSuper ? 'Last Super Admin' : 'Super Admin only') }}</span>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
