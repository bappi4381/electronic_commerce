@extends('admin.layouts')
@section('title', 'Edit ' . $admin->name)
@section('ui-v2', '1')

@section('content')
<x-admin.page-header :title="'Edit ' . $admin->name" description="Update credentials and roles."
                     :breadcrumbs="['Admin users' => route('admins.index'), $admin->name => null]" />

<form method="POST" action="{{ route('admins.update', $admin) }}" class="space-y-6">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-admin.card title="Account" class="lg:col-span-2">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-admin.input name="name" label="Full name" required :value="$admin->name" />
                <x-admin.input name="email" type="email" label="Email" required :value="$admin->email" autocomplete="off" />
                <x-admin.password name="password" label="New password" autocomplete="new-password" hint="Leave empty to keep the current password." />
                <x-admin.password name="password_confirmation" label="Confirm new password" autocomplete="new-password" />
            </div>
        </x-admin.card>

        <x-admin.card title="Roles" class="self-start" x-data>
            @unless($isLastSuperAdmin)
                <x-slot:actions>
                    <button type="button" @click="$root.querySelectorAll('.role-checkbox:not(:disabled)').forEach(cb => cb.checked = false)" class="rounded text-xs font-medium text-gray-500 hover:text-gray-900 focus-ring dark:text-gray-400 dark:hover:text-white">Clear</button>
                </x-slot:actions>
            @endunless
            <div class="space-y-4">
                @forelse($roles as $role)
                    @php($pinned = $isLastSuperAdmin && $role->name === \App\Support\AdminPermissions::SUPER_ADMIN_ROLE)
                    @if($pinned)
                        <input type="hidden" name="roles[]" value="{{ $role->id }}">
                        <x-admin.checkbox name="roles_locked[]" :id="'role-locked-' . $role->id" :value="$role->id" :label="$role->name" :checked="true" disabled class="role-checkbox"
                                          description="This is the last Super Admin. Give the role to someone else first." />
                    @else
                        <x-admin.checkbox name="roles[]" :value="$role->id" :label="$role->name" class="role-checkbox"
                                          :checked="in_array($role->id, $adminRoles)" :description="'Grants every permission in ' . $role->name . '.'" />
                    @endif
                @empty
                    <x-admin.empty-state title="No roles yet" icon="bi-shield-lock" class="py-6" />
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('admins.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
    </div>
</form>
@endsection
