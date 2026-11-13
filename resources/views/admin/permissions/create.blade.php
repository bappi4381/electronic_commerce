@extends('admin.layouts')
@section('title', 'New permission')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="New permission" description="A custom access key you can grant through roles."
                     :breadcrumbs="['Roles' => route('roles.index'), 'Permissions' => route('permissions.index'), 'New' => null]" />

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ name: @js(old('name', '')) }">
    <form method="POST" action="{{ route('permissions.store') }}" class="lg:col-span-2">
        @csrf
        <x-admin.card title="Permission">
            <x-admin.input name="name" label="Identifier" required prefix="bi-key" autocomplete="off" placeholder="e.g. reports.export" class="font-mono"
                           x-model="name" @input="name = name.toLowerCase().replace(/[\s_]+/g, '-')"
                           hint="Lowercase letters, numbers, dots and hyphens." />
            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-lg bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800/60">
                <x-admin.status-badge tone="gray" :dot="false" label="guard: admin" />
                <i class="bi bi-arrow-right text-gray-400" aria-hidden="true"></i>
                <code class="font-mono font-medium text-gray-900 dark:text-white" x-text="name || '—'">—</code>
            </div>
            <x-slot:footer>
                <x-admin.button variant="secondary" :href="route('permissions.index')">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="bi-check-lg">Create permission</x-admin.button>
            </x-slot:footer>
        </x-admin.card>
    </form>

    <x-admin.card title="Naming guide" class="self-start">
        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> Use <code class="font-mono text-xs">area.action</code></li>
            <li class="flex gap-2"><i class="bi bi-check-circle-fill mt-0.5 text-green-600 dark:text-green-400" aria-hidden="true"></i> Be specific and descriptive</li>
            <li class="flex gap-2"><i class="bi bi-x-circle-fill mt-0.5 text-red-600 dark:text-red-400" aria-hidden="true"></i> No spaces or uppercase letters</li>
        </ul>
        <p class="mt-6 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Examples</p>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach(['reports.export', 'analytics.view', 'inventory.audit'] as $example)
                <button type="button" @click="name = @js($example)" class="rounded-md border border-gray-200 px-2 py-1 font-mono text-xs text-gray-700 hover:border-brand-400 hover:text-brand-700 focus-ring dark:border-gray-700 dark:text-gray-300 dark:hover:text-brand-300">{{ $example }}</button>
            @endforeach
        </div>
    </x-admin.card>
</div>
@endsection
