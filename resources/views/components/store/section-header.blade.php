@props(['title', 'href' => null, 'linkLabel' => null, 'icon' => null, 'id' => null])

<div {{ $attributes->merge(['class' => 'mb-3 flex items-center justify-between gap-3 sm:mb-5']) }}>
    <div class="flex min-w-0 items-center gap-2.5">
        @if($icon)
            <span class="flex size-8 shrink-0 items-center justify-center rounded-[var(--sf-radius-sm)] bg-brand-50 text-brand-600 sm:size-9" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
        @endif
        <h2 @if($id) id="{{ $id }}" @endif class="truncate font-display text-lg font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $title }}</h2>
        {{ $after ?? '' }}
    </div>
    <div class="flex shrink-0 items-center gap-3">
        {{ $slot }}
        @if($href)
            <a href="{{ $href }}" class="inline-flex min-h-11 items-center gap-1 rounded text-sm font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">
                {{ $linkLabel ?? __('View all') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
            </a>
        @endif
    </div>
</div>
