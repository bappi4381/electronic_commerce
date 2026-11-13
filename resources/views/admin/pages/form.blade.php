@extends('admin.layouts')
@section('title', $page->exists ? 'Edit page' : 'New page')
@section('ui-v2', '1')

@section('content')
@php
    $t = fn (string $field, string $locale) => $page->exists ? $page->getTranslation($field, $locale, false) : '';
    $bnError = collect($errors->keys())->contains(fn ($key) => str_ends_with($key, '_bn'));
    $title = $page->exists ? $page->getTranslation('title', 'en') : 'New page';
@endphp

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
      x-data="{ lang: @js($bnError && !$errors->has('title_en') ? 'bn' : 'en'), slug: @js(old('slug', $page->slug)) }">
    @csrf
    @if($page->exists) @method('PUT') @endif

    <x-admin.page-header :title="$title" description="Translatable content shown at /page/{slug} on the storefront."
                         :breadcrumbs="['Pages' => route('admin.pages.index'), $title => null]">
        @if($page->exists && $page->is_published)
            <x-admin.button variant="ghost" :href="route('pages.show', ['locale' => 'en', 'slug' => $page->slug])" target="_blank" rel="noopener" icon="bi-box-arrow-up-right">View on site</x-admin.button>
        @endif
        <x-admin.button variant="secondary" :href="route('admin.pages.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">{{ $page->exists ? 'Save page' : 'Create page' }}</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-admin.card :padding="false" class="min-w-0 xl:col-span-2">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-3 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Content</h2>
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800" role="group" aria-label="Language">
                    @foreach(['en' => 'English', 'bn' => 'বাংলা'] as $code => $name)
                        <button type="button" @click="lang = '{{ $code }}'" :aria-pressed="(lang === '{{ $code }}').toString()"
                                class="rounded-md px-3 py-1 text-xs font-medium focus-ring"
                                :class="lang === '{{ $code }}' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ $name }}</button>
                    @endforeach
                </div>
            </div>
            @foreach(['en', 'bn'] as $code)
                <div x-show="lang === '{{ $code }}'" @if($code !== 'en') x-cloak @endif class="space-y-4 p-6">
                    <x-admin.input :name="'title_' . $code" :label="$code === 'en' ? 'Title' : 'শিরোনাম'" :required="$code === 'en'" :optional="$code !== 'en'" maxlength="255" :value="$t('title', $code)" />
                    <x-admin.textarea :name="'content_' . $code" :label="$code === 'en' ? 'Content' : 'বিষয়বস্তু'" rows="18" :value="$t('content', $code)" class="font-mono text-[13px] leading-relaxed"
                                      :hint="'HTML allowed: headings, lists, links, bold. Scripts and unsafe attributes are removed when saving.' . ($code === 'bn' ? ' Leave empty to show the English version.' : '')" />
                    <x-admin.input :name="'meta_description_' . $code" label="Meta description" optional maxlength="300" :value="$t('meta_description', $code)"
                                   hint="Shown by search engines under the page title." />
                </div>
            @endforeach
        </x-admin.card>

        <x-admin.card title="Settings" class="self-start">
            <div class="space-y-4">
                <div>
                    <x-admin.input name="slug" label="URL slug" required maxlength="100" class="font-mono" x-model="slug" :readonly="$page->isCore()" :old="false" />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        /page/<span class="font-mono" x-text="slug || 'your-slug'">{{ $page->slug ?: 'your-slug' }}</span>{{ $page->isCore() ? ' · fixed for footer links' : '' }}
                    </p>
                </div>
                <x-admin.input name="sort_order" type="number" label="Sort order" min="0" :value="$page->sort_order" hint="Lower numbers appear first in the footer." />
                <x-admin.toggle name="is_published" label="Published" description="Draft pages are hidden from the storefront." :checked="(bool) $page->is_published" />
            </div>
        </x-admin.card>
    </div>
</form>
@endsection
