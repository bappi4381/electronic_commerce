@extends('admin.layouts')
@section('title', 'Attributes')
@section('ui-v2', '1')

@section('content')
@php
    $types = ['select' => 'Dropdown', 'radio' => 'Radio buttons', 'text' => 'Text input'];
    $tabs = [
        'library' => ['label' => 'Attributes & values', 'icon' => 'bi-tags'],
        'mapping' => ['label' => 'Category mapping', 'icon' => 'bi-diagram-3'],
    ];
@endphp

<div x-data="{ tab: localStorage.getItem('attributeManagerTab') || 'library' }" x-init="$watch('tab', value => localStorage.setItem('attributeManagerTab', value))">
    <x-admin.page-header title="Attributes" description="Options like colour, size, RAM or storage that product variants are built from." />

    <x-admin.tabs :tabs="$tabs" label="Attribute sections" class="mb-6" />

    <x-admin.tab-panel name="library">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @can('products.create')
                <x-admin.card title="New attribute" class="self-start">
                    <form method="POST" action="{{ route('admin.attributes.store') }}" class="space-y-4">
                        @csrf
                        <x-admin.input name="name" id="new-attribute-name" label="Name" required placeholder="e.g. Colour, Size, Storage" />
                        <x-admin.select name="type" id="new-attribute-type" label="Input type" :options="$types" value="select" />
                        <x-admin.checkbox name="is_filterable" id="new-attribute-filterable" label="Filterable" description="Show it in the shop's filter sidebar." :checked="true" />
                        <x-admin.button type="submit" icon="bi-plus-lg" class="w-full">Create attribute</x-admin.button>
                    </form>
                </x-admin.card>
            @endcan

            <div @class(['min-w-0 space-y-4', 'lg:col-span-2' => auth()->user()->can('products.create'), 'lg:col-span-3' => !auth()->user()->can('products.create')])>
                @forelse($attributes as $attribute)
                    <x-admin.card :padding="false" x-data="{ editing: false, adding: false }">
                        <div class="flex flex-wrap items-start justify-between gap-4 px-6 py-4">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $attribute->name }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <x-admin.status-badge tone="gray" :dot="false" :label="$types[$attribute->type] ?? ucfirst($attribute->type)" />
                                    @if($attribute->is_filterable)<x-admin.status-badge tone="green" :dot="false" label="Filterable" />@endif
                                    <x-admin.status-badge tone="gray" :dot="false" :label="$attribute->values_count . ' ' . \Illuminate\Support\Str::plural('value', $attribute->values_count)" />
                                    @foreach($attribute->categories as $cat)
                                        <x-admin.status-badge tone="brand" :dot="false" :label="$cat->getTranslation('name', 'en')" />
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                @can('products.create')
                                    <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" @click="adding = !adding; editing = false" ::aria-expanded="adding.toString()">Add value</x-admin.button>
                                @endcan
                                @can('products.edit')
                                    <x-admin.button variant="ghost" size="sm" icon="bi-pencil" title="Edit" aria-label="Edit {{ $attribute->name }}" @click="editing = !editing; adding = false" />
                                @endcan
                                @can('products.delete')
                                    <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}"
                                          data-confirm="Its values are removed from the catalog. Variants that use them lose that option." data-confirm-title="Delete {{ $attribute->name }}?" data-confirm-button="Delete">
                                        @csrf @method('DELETE')
                                        <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $attribute->name }}" />
                                    </form>
                                @endcan
                            </div>
                        </div>

                        @can('products.edit')
                            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.attributes.update', $attribute) }}"
                                  class="grid grid-cols-1 gap-4 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-2 dark:border-gray-800 dark:bg-gray-900/60">
                                @csrf @method('PUT')
                                <x-admin.input name="name" :id="'attr-name-' . $attribute->id" label="Name" required :value="$attribute->name" :old="false" />
                                <x-admin.field label="Input type" :for="'attr-type-' . $attribute->id">
                                    <select id="attr-type-{{ $attribute->id }}" name="type"
                                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                        @foreach($types as $value => $label)
                                            <option value="{{ $value }}" @selected($attribute->type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </x-admin.field>
                                <div class="flex flex-wrap items-center justify-between gap-3 sm:col-span-2">
                                    <x-admin.checkbox name="is_filterable" :id="'attr-filterable-' . $attribute->id" label="Filterable" :checked="(bool) $attribute->is_filterable" />
                                    <div class="flex gap-2">
                                        <x-admin.button variant="secondary" size="sm" @click="editing = false">Cancel</x-admin.button>
                                        <x-admin.button type="submit" size="sm">Save</x-admin.button>
                                    </div>
                                </div>
                            </form>
                        @endcan

                        @can('products.create')
                            <form x-show="adding" x-cloak method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}"
                                  class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-end dark:border-gray-800 dark:bg-gray-900/60">
                                @csrf
                                <div class="flex-1">
                                    <x-admin.input name="value" :id="'attr-value-' . $attribute->id" label="New value" required placeholder="e.g. Red, XL, 128 GB" :old="false" />
                                </div>
                                <x-admin.button type="submit" icon="bi-plus-lg">Add</x-admin.button>
                            </form>
                        @endcan

                        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            @if($attribute->values->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">No values yet. Add the options shoppers can choose from.</p>
                            @else
                                <ul class="flex flex-wrap gap-2">
                                    @foreach($attribute->values as $value)
                                        <li class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white py-1 pl-2.5 pr-1 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $value->value }}
                                            @can('products.delete')
                                                <form method="POST" action="{{ route('admin.attributes.values.destroy', $value) }}"
                                                      data-confirm="Variants using “{{ $value->value }}” lose this option." data-confirm-title="Remove {{ $value->value }}?" data-confirm-button="Remove">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex size-5 items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-600 focus-ring dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                                        <span class="sr-only">Remove {{ $value->value }}</span><i class="bi bi-x text-base" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </x-admin.card>
                @empty
                    <x-admin.card>
                        <x-admin.empty-state title="No attributes yet" description="Create attributes like Colour or Storage, then add their values." icon="bi-tags" />
                    </x-admin.card>
                @endforelse
            </div>
        </div>
    </x-admin.tab-panel>

    <x-admin.tab-panel name="mapping">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @can('products.edit')
                <x-admin.card title="Assign attributes" description="Pick a category and the attributes its products can use." class="self-start">
                    <form method="POST" action="{{ route('admin.attributes.attachToCategory') }}" class="space-y-4">
                        @csrf
                        <x-admin.field label="Category" for="map-category" error="category_id" required>
                            <select id="map-category" name="category_id" required
                                    class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">Choose a category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->getTranslation('name', 'en') }}</option>
                                    @foreach($cat->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;↳ {{ $child->getTranslation('name', 'en') }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </x-admin.field>
                        <fieldset>
                            <legend class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Attributes</legend>
                            <div class="max-h-72 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                @forelse($attributes as $attribute)
                                    <x-admin.checkbox name="attribute_ids[]" :value="$attribute->id" :label="$attribute->name" :description="$types[$attribute->type] ?? ucfirst($attribute->type)" />
                                @empty
                                    <p class="py-2 text-center text-sm text-gray-500 dark:text-gray-400">No attributes created yet.</p>
                                @endforelse
                            </div>
                        </fieldset>
                        <x-admin.button type="submit" icon="bi-check-lg" class="w-full">Save assignments</x-admin.button>
                    </form>
                </x-admin.card>
            @endcan

            <x-admin.card title="Current assignments" :padding="false" :class="auth()->user()->can('products.edit') ? 'min-w-0 lg:col-span-2' : 'min-w-0 lg:col-span-3'">
                <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($categories as $cat)
                        @foreach(collect([$cat])->concat($cat->children) as $node)
                            <li @class(['flex flex-col gap-2 px-6 py-3 sm:flex-row sm:items-center', 'sm:pl-12' => $node->parent_id])>
                                <p class="w-48 shrink-0 text-sm font-medium text-gray-900 dark:text-white">
                                    @if($node->parent_id)<span class="text-gray-400" aria-hidden="true">↳ </span>@endif{{ $node->getTranslation('name', 'en') }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($node->attributes as $attribute)
                                        <x-admin.status-badge tone="gray" :dot="false" :label="$attribute->name" />
                                    @empty
                                        <span class="text-xs text-gray-400 dark:text-gray-500">No attributes assigned</span>
                                    @endforelse
                                </div>
                            </li>
                        @endforeach
                    @empty
                        <li><x-admin.empty-state title="No categories yet" icon="bi-diagram-3" /></li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </x-admin.tab-panel>
</div>
@endsection
