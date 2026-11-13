@extends('admin.layouts')
@section('title', 'Brands')
@section('ui-v2', '1')

@section('content')
<div x-data="{
        brand: { id: null, name: '', status: true, logo: null },
        edit(data) { this.brand = data; this.$dispatch('open-modal', 'edit-brand'); },
     }">
    <x-admin.page-header title="Brands" :description="number_format($brands->total()) . ' ' . \Illuminate\Support\Str::plural('brand', $brands->total()) . '. Brands with products can\'t be deleted; deactivate them instead.'" />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        @can('products.create')
            <x-admin.card title="Add brand" class="self-start">
                <form method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <x-admin.input name="name" id="new-brand-name" label="Name" required maxlength="100" placeholder="e.g. Samsung" />
                    <x-admin.input name="logo" id="new-brand-logo" type="file" label="Logo" optional accept="image/jpeg,image/png,image/webp" hint="JPG, PNG or WebP up to 2 MB." />
                    <x-admin.toggle name="status" id="new-brand-status" label="Active" description="Inactive brands are hidden from store filters." :checked="true" />
                    <x-admin.button type="submit" icon="bi-plus-lg" class="w-full">Add brand</x-admin.button>
                </form>
            </x-admin.card>
        @endcan

        <x-admin.data-table :paginator="$brands" search="search" search-placeholder="Search brands…" :class="auth()->user()->can('products.create') ? 'min-w-0 xl:col-span-2' : 'min-w-0 xl:col-span-3'"
            :columns="[
                ['label' => 'Brand', 'sortable' => true],
                ['label' => 'Products', 'sortable' => true, 'align' => 'right'],
                ['label' => 'Status'],
                ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
            ]"
            empty-title="No brands yet" empty-description="Add brands so products can be filtered by manufacturer." empty-icon="bi-award">
            @foreach($brands as $brand)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary data-sort="{{ $brand->name }}">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800">
                                @if($brand->logo)
                                    <img src="{{ asset('storage/' . $brand->logo) }}" alt="" class="max-h-full max-w-full object-contain p-1" loading="lazy">
                                @else
                                    <span class="text-xs font-semibold uppercase text-gray-400" aria-hidden="true">{{ mb_substr($brand->name, 0, 2) }}</span>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $brand->name }}</span>
                                <span class="block truncate font-mono text-xs font-normal text-gray-500 dark:text-gray-400">{{ $brand->slug }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Products" data-sort="{{ $brand->products_count }}" class="text-right tabular-nums">
                        <a href="{{ route('admin.products.index', ['brand' => $brand->id]) }}" class="rounded font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">{{ number_format($brand->products_count) }}</a>
                    </td>
                    <td data-label="Status"><x-admin.status-badge :status="$brand->status ? 'active' : 'inactive'" /></td>
                    <td class="whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            @can('products.edit')
                                <x-admin.button variant="ghost" size="sm" icon="bi-pencil" title="Edit" aria-label="Edit {{ $brand->name }}"
                                                @click="edit({{ \Illuminate\Support\Js::from(['id' => $brand->id, 'name' => $brand->name, 'status' => (bool) $brand->status, 'logo' => $brand->logo ? asset('storage/' . $brand->logo) : null]) }})" />
                            @endcan
                            @can('products.delete')
                                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}"
                                      data-confirm="{{ $brand->products_count ? 'This brand still has products, so it can\'t be deleted. Deactivate it instead.' : 'The brand and its logo are removed permanently.' }}"
                                      data-confirm-title="Delete {{ $brand->name }}?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $brand->name }}" />
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>

    @can('products.edit')
        <x-admin.modal name="edit-brand" title="Edit brand">
            <form id="edit-brand-form" method="POST" enctype="multipart/form-data" class="space-y-4"
                  :action="@js(route('admin.brands.update', ['brand' => '__ID__'])).replace('__ID__', brand.id)">
                @csrf @method('PUT')
                <x-admin.input name="name" id="edit-brand-name" label="Name" required maxlength="100" x-model="brand.name" :old="false" />
                <div class="flex items-center gap-4">
                    <template x-if="brand.logo">
                        <img :src="brand.logo" alt="Current logo" class="size-14 shrink-0 rounded-lg border border-gray-200 bg-white object-contain p-1 dark:border-gray-800">
                    </template>
                    <div class="min-w-0 flex-1">
                        <x-admin.input name="logo" id="edit-brand-logo" type="file" label="Replace logo" optional accept="image/jpeg,image/png,image/webp" hint="JPG, PNG or WebP up to 2 MB." />
                    </div>
                </div>
                <template x-if="brand.logo">
                    <x-admin.checkbox name="remove_logo" id="edit-brand-remove-logo" label="Remove the current logo" />
                </template>
                <div class="flex items-start justify-between gap-4">
                    <label for="edit-brand-status" class="text-sm">
                        <span class="font-medium text-gray-800 dark:text-gray-200">Active</span>
                        <span class="block text-gray-500 dark:text-gray-400">Inactive brands are hidden from store filters.</span>
                    </label>
                    <input type="hidden" name="status" :value="brand.status ? 1 : 0">
                    <button type="button" id="edit-brand-status" role="switch" :aria-checked="brand.status.toString()" @click="brand.status = !brand.status"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-ring"
                            :class="brand.status ? 'bg-brand-600 dark:bg-brand-500' : 'bg-gray-200 dark:bg-gray-700'">
                        <span class="sr-only">Active</span>
                        <span aria-hidden="true" class="pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm transition-transform" :class="brand.status ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </div>
            </form>
            <x-slot:footer>
                <x-admin.button variant="secondary" @click="$dispatch('close-modal', 'edit-brand')">Cancel</x-admin.button>
                <x-admin.button type="submit" form="edit-brand-form">Save brand</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endcan
</div>
@endsection
