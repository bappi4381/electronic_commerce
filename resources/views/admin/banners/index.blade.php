@extends('admin.layouts')
@section('title', 'Banners')
@section('ui-v2', '1')

@section('content')
<div x-data="bannerForm()">
    <x-admin.page-header title="Banners" :description="$banners->count() . ' ' . \Illuminate\Support\Str::plural('banner', $banners->count()) . ' across the hero slider and promotional slots.'">
        @can('banners.create')
            <x-admin.button icon="bi-plus-lg" @click="openWith({})">Add banner</x-admin.button>
        @endcan
    </x-admin.page-header>

    @if($banners->isEmpty())
        <x-admin.card>
            <x-admin.empty-state title="No banners yet" description="Upload a hero slide or promotional banner for the storefront." icon="bi-images">
                @can('banners.create')
                    <x-admin.button size="sm" icon="bi-plus-lg" @click="openWith({})">Add banner</x-admin.button>
                @endcan
            </x-admin.empty-state>
        </x-admin.card>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($banners as $banner)
                <article @class(['flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900', 'opacity-75' => !$banner->status])>
                    <div class="relative aspect-[16/7] bg-gray-100 dark:bg-gray-800">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner image' }}" class="size-full object-cover" loading="lazy">
                        <div class="absolute left-3 top-3 flex gap-1.5">
                            <x-admin.status-badge tone="indigo" :dot="false" :label="$types[$banner->type] ?? ucfirst($banner->type)" class="bg-white/90 backdrop-blur dark:bg-gray-900/80" />
                            <x-admin.status-badge tone="gray" :dot="false" :label="'#' . $banner->order" class="bg-white/90 backdrop-blur dark:bg-gray-900/80" />
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-1 px-5 py-4">
                        <p class="truncate font-medium text-gray-900 dark:text-white">{{ $banner->title ?: 'Untitled banner' }}</p>
                        @if($banner->subtitle)<p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $banner->subtitle }}</p>@endif
                        @if($banner->link)
                            <p class="mt-1 flex items-center gap-1 truncate font-mono text-xs text-brand-600 dark:text-brand-400"><i class="bi bi-link-45deg" aria-hidden="true"></i><span class="truncate">{{ $banner->link }}</span></p>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-800">
                        @can('banners.edit')
                            <form method="POST" action="{{ route('banners.toggle', $banner) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <button type="submit" role="switch" aria-checked="{{ $banner->status ? 'true' : 'false' }}"
                                        @class(['relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus-ring',
                                                'bg-brand-600 dark:bg-brand-500' => $banner->status, 'bg-gray-200 dark:bg-gray-700' => !$banner->status])>
                                    <span class="sr-only">{{ $banner->status ? 'Hide' : 'Show' }} {{ $banner->title ?: 'banner' }}</span>
                                    <span aria-hidden="true" @class(['pointer-events-none inline-block size-5 rounded-full bg-white shadow-sm transition-transform', 'translate-x-5' => $banner->status])></span>
                                </button>
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $banner->status ? 'Visible' : 'Hidden' }}</span>
                            </form>
                        @else
                            <x-admin.status-badge :status="$banner->status ? 'active' : 'inactive'" :label="$banner->status ? 'Visible' : 'Hidden'" />
                        @endcan
                        <div class="flex items-center gap-1">
                            @can('banners.edit')
                                <x-admin.button variant="ghost" size="sm" icon="bi-pencil" title="Edit" aria-label="Edit {{ $banner->title ?: 'banner' }}"
                                                @click="openWith({{ \Illuminate\Support\Js::from(['id' => $banner->id, 'title' => $banner->title, 'subtitle' => $banner->subtitle, 'link' => $banner->link, 'type' => $banner->type, 'order' => $banner->order, 'image' => asset('storage/' . $banner->image), 'action' => route('banners.update', $banner)]) }})" />
                            @endcan
                            @can('banners.delete')
                                <form method="POST" action="{{ route('banners.destroy', $banner) }}"
                                      data-confirm="The banner and its image are removed from the storefront." data-confirm-title="Delete this banner?" data-confirm-button="Delete">
                                    @csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete {{ $banner->title ?: 'banner' }}" />
                                </form>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <x-admin.modal name="banner" max-width="2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white" x-text="banner.id ? 'Edit banner' : 'Add banner'">Add banner</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">JPG, PNG or WebP, up to 5 MB.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'banner')" class="-m-1 rounded-lg p-1 text-gray-400 hover:text-gray-600 focus-ring dark:hover:text-gray-200">
                <span class="sr-only">Close</span><i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <form id="banner-form" :action="banner.action" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <template x-if="banner.id"><input type="hidden" name="_method" value="PUT"></template>
            <x-admin.input name="title" id="banner-title" label="Title" optional maxlength="255" x-model="banner.title" :old="false" />
            <x-admin.input name="subtitle" id="banner-subtitle" label="Subtitle" optional maxlength="255" x-model="banner.subtitle" :old="false" />
            <x-admin.input name="link" id="banner-link" label="Link" optional maxlength="255" x-model="banner.link" :old="false" placeholder="https://… or /en/products?category=3" />
            <div class="grid grid-cols-2 gap-4">
                <x-admin.field label="Placement" for="banner-type" required>
                    <select id="banner-type" name="type" x-model="banner.type" required
                            class="block h-10 w-full rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.input name="order" id="banner-order" type="number" label="Display order" required min="0" max="1000" x-model="banner.order" :old="false" hint="Lower numbers show first." />
            </div>
            <div>
                <template x-if="banner.image">
                    <img :src="banner.image" alt="Current banner image" class="mb-3 h-32 w-full rounded-lg object-cover">
                </template>
                <x-admin.field for="banner-image" error="image">
                    <label for="banner-image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" x-text="banner.id ? 'Replace image (optional)' : 'Image'">Image</label>
                    <input type="file" id="banner-image" name="image" accept="image/jpeg,image/png,image/webp" :required="!banner.id"
                           class="block w-full rounded-lg border border-gray-300 bg-white p-1.5 text-sm text-gray-900 shadow-xs file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:file:bg-gray-800 dark:file:text-gray-200">
                </x-admin.field>
            </div>
        </form>
        <x-slot:footer>
            <x-admin.button variant="secondary" @click="$dispatch('close-modal', 'banner')">Cancel</x-admin.button>
            <x-admin.button type="submit" form="banner-form"><span x-text="banner.id ? 'Save banner' : 'Upload banner'">Upload banner</span></x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>

@push('scripts')
<script>
    function bannerForm() {
        const blank = { id: null, title: '', subtitle: '', link: '', type: 'hero', order: 1, image: null, action: @js(route('banners.store')) };
        return {
            banner: { ...blank },
            openWith(data) {
                this.banner = { ...blank, ...(data || {}) };
                this.$dispatch('open-modal', 'banner');
            },
        };
    }
</script>
@endpush
@endsection
