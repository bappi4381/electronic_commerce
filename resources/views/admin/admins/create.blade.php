@extends('admin.layouts')
@section('title', 'Add admin')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Add admin" description="Give a team member access to the admin panel."
                     :breadcrumbs="['Admin users' => route('admins.index'), 'Add admin' => null]" />

<form method="POST" action="{{ route('admins.store') }}" class="space-y-6">
    @csrf
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-admin.card title="Account" class="lg:col-span-2">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-admin.input name="name" label="Full name" required placeholder="e.g. Jane Doe" />
                <x-admin.input name="email" type="email" label="Email" required placeholder="jane@example.com" autocomplete="off" />
                <x-admin.password name="password" label="Password" required autocomplete="new-password" />
                <x-admin.password name="password_confirmation" label="Confirm password" required autocomplete="new-password" />
            </div>
        </x-admin.card>

        <x-admin.card title="Roles" class="self-start" x-data>
            <x-slot:actions>
                <button type="button" @click="$root.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false)" class="rounded text-xs font-medium text-gray-500 hover:text-gray-900 focus-ring dark:text-gray-400 dark:hover:text-white">Clear</button>
            </x-slot:actions>
            <div class="space-y-4">
                @forelse($roles as $role)
                    <x-admin.checkbox name="roles[]" :value="$role->id" :label="$role->name" class="role-checkbox"
                                      :description="'Grants every permission in ' . $role->name . '.'" />
                @empty
                    <x-admin.empty-state title="No roles yet" icon="bi-shield-lock" class="py-6">
                        <x-admin.button size="sm" variant="secondary" :href="route('roles.create')">Create a role</x-admin.button>
                    </x-admin.empty-state>
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('admins.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Create admin</x-admin.button>
    </div>
</form>
@endsection
