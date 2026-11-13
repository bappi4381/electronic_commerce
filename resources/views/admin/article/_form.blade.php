{{-- Shared article fields. Expects $categories and optional $article. --}}
@php
    $editing = isset($article);
    $slugify = "value.toLowerCase().replace(/[^\\w\\s-]/g, '').trim().replace(/\\s+/g, '-').replace(/-+/g, '-')";
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3"
     x-data="{
        title: @js(old('title', $article->title ?? '')),
        slug: @js(old('slug', $article->slug ?? '')),
        preview: @js($editing && $article->image ? asset('storage/' . $article->image) : null),
        slugFrom(value) { return {{ $slugify }}; },
        pick(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => this.preview = e.target.result;
            reader.readAsDataURL(file);
        },
     }">
    <div class="min-w-0 space-y-6 xl:col-span-2">
        <x-admin.card>
            <div class="space-y-6">
                <x-admin.input name="title" id="title" label="Headline" required placeholder="A clear, compelling title" class="h-12 text-base font-medium"
                               x-model="title" @input="{{ $editing ? '' : 'slug = slugFrom(title)' }}" />
                <x-admin.textarea name="content" label="Content" required rows="20" :value="$article->content ?? ''"
                                  placeholder="Start writing…" hint="Basic HTML (headings, lists, links, images) is supported." class="font-mono text-[13px] leading-relaxed" />
            </div>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Publishing">
            <div class="space-y-4">
                <x-admin.select name="category_id" label="Category" required placeholder="Select a category" :value="$article->category_id ?? null"
                                :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->name])->all()" />
                <x-admin.input name="published_at" type="date" label="Publish date" optional :value="$editing ? optional($article->published_at)->format('Y-m-d') : date('Y-m-d')" />
                <x-admin.field label="Status">
                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" role="radiogroup" aria-label="Status">
                        @foreach(['draft' => 'Draft', 'published' => 'Published'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $value }}" class="peer sr-only" @checked(old('status', $article->status ?? 'draft') === $value)>
                                <span class="block rounded-md px-3 py-1.5 text-center text-sm font-medium text-gray-600 transition-colors peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow-xs peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 dark:text-gray-300 dark:peer-checked:bg-gray-700 dark:peer-checked:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-admin.field>
                <div>
                    <x-admin.input name="slug" label="URL slug" :required="$editing" x-model="slug" :readonly="!$editing" class="font-mono text-xs"
                                   :hint="$editing ? 'Changing the slug breaks existing links to this article.' : 'Generated from the headline.'" />
                    @if($editing)
                        <button type="button" @click="slug = slugFrom(title)" class="mt-1.5 rounded text-xs font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Regenerate from headline</button>
                    @endif
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Featured image">
            <label for="image-input" class="group relative block cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:border-brand-400 focus-within:ring-2 focus-within:ring-brand-500/40 dark:border-gray-700">
                <template x-if="preview">
                    <span class="block aspect-video">
                        <img :src="preview" alt="Featured image preview" class="size-full object-cover">
                        <span class="absolute inset-0 flex items-center justify-center bg-gray-950/50 text-sm font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">Change image</span>
                    </span>
                </template>
                <template x-if="!preview">
                    <span class="flex flex-col items-center justify-center px-6 py-10 text-center">
                        <i class="bi bi-cloud-arrow-up mb-2 text-2xl text-gray-400 group-hover:text-brand-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Choose an image</span>
                        <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">PNG, JPG or WebP, up to 2 MB</span>
                    </span>
                </template>
                <input type="file" name="image" id="image-input" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
            </label>
            @error('image')<p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </x-admin.card>
    </div>
</div>
