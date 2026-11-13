@extends('admin.layouts')
@section('title', 'Import products')
@section('ui-v2', '1')

@section('content')
@php
    $columns = [
        ['name_en', true, 'Product name in English. Rows with the same name become one product.'],
        ['name_bn', false, 'Product name in Bengali.'],
        ['description_en', false, 'Description in English. Taken from the first row.'],
        ['description_bn', false, 'Description in Bengali. Taken from the first row.'],
        ['category', false, 'Category name. Created automatically if not found.'],
        ['price', true, 'Base price of the product (e.g. 500).'],
        ['discount', false, 'Discount percentage (0–100). Default: 0.'],
        ['brand', false, 'Brand name.'],
        ['model', false, 'Model name.'],
        ['sku', false, 'Unique SKU for this variant.'],
        ['variant_price', false, 'Price for this variant. Leave blank to use the base price.'],
        ['stock', true, 'Stock quantity for this variant.'],
        ['attribute_color', false, 'Value for the "Color" attribute (e.g. Red, Blue).'],
        ['attribute_size', false, 'Value for the "Size" attribute (e.g. M, L, XL).'],
        ['attribute_*', false, 'Any attribute, named attribute_{attribute_name}.'],
    ];
    $steps = [
        'Download the <strong>CSV template</strong> and fill in your products.',
        'Each <strong>row is one variant</strong>. Add one row per variant (e.g. Red-M, Red-L) with the same <code>name_en</code>.',
        'Attribute columns like <code>attribute_color</code> are dynamic; add any attribute you need.',
        'Upload the file and start the import. Products are created automatically.',
    ];
@endphp

<x-admin.page-header title="Import products" description="Create products and variants in bulk from an Excel or CSV file."
                     :breadcrumbs="['Products' => route('admin.products.index'), 'Import' => null]">
    <x-admin.button variant="secondary" :href="route('admin.products.import.template')" icon="bi-file-earmark-arrow-down">Download template</x-admin.button>
</x-admin.page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
    <div class="space-y-6 lg:col-span-3">
        <x-admin.card title="Upload file">
            <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" x-data="{ fileName: '', over: false }">
                @csrf
                <label for="fileInput"
                       @dragover.prevent="over = true" @dragleave.prevent="over = false"
                       @drop.prevent="over = false; $refs.file.files = $event.dataTransfer.files; fileName = $refs.file.files[0]?.name || ''"
                       class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-12 text-center transition-colors focus-within:ring-2 focus-within:ring-brand-500/40"
                       :class="over ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-gray-300 hover:border-brand-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/40'">
                    <span class="mb-3 flex size-12 items-center justify-center rounded-full bg-brand-50 text-xl text-brand-600 dark:bg-brand-500/10 dark:text-brand-400" aria-hidden="true">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="fileName || 'Drop your file here or click to browse'">Drop your file here or click to browse</span>
                    <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">.xlsx, .xls or .csv, up to 10 MB</span>
                    <input type="file" name="file" id="fileInput" x-ref="file" accept=".xlsx,.xls,.csv" required class="sr-only"
                           @change="fileName = $event.target.files[0]?.name || ''">
                </label>
                @error('file')<p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                <x-admin.button type="submit" icon="bi-upload" class="mt-4 w-full">Start import</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.card title="Column reference" :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th scope="col" class="px-6 py-3">Column</th>
                            <th scope="col" class="px-6 py-3">Required</th>
                            <th scope="col" class="px-6 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($columns as [$col, $required, $description])
                            <tr>
                                <td class="whitespace-nowrap px-6 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ $col }}</code></td>
                                <td class="px-6 py-3">
                                    @if($required)
                                        <x-admin.status-badge tone="red" :dot="false" label="Required" />
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Optional</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>

    <x-admin.card title="How it works" class="self-start lg:col-span-2">
        <ol class="space-y-4 text-sm text-gray-600 dark:text-gray-300 [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1 [&_code]:text-xs dark:[&_code]:bg-gray-800">
            @foreach($steps as $step)
                <li class="flex gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300" aria-hidden="true">{{ $loop->iteration }}</span>
                    <span>{!! $step !!}</span>
                </li>
            @endforeach
        </ol>
    </x-admin.card>
</div>
@endsection
