@props(['count' => 5])

@for($i = 0; $i < $count; $i++)
    <div {{ $attributes->merge(['class' => 'rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-2.5 sm:p-3']) }} aria-hidden="true">
        <div class="sf-skeleton aspect-square rounded-[var(--sf-radius-sm)]"></div>
        <div class="sf-skeleton mt-3 h-2.5 w-1/3 rounded"></div>
        <div class="sf-skeleton mt-2 h-3 w-full rounded"></div>
        <div class="sf-skeleton mt-1.5 h-3 w-2/3 rounded"></div>
        <div class="mt-3 flex items-center justify-between">
            <div class="sf-skeleton h-4 w-1/3 rounded"></div>
            <div class="sf-skeleton size-9 rounded-[var(--sf-radius-sm)]"></div>
        </div>
    </div>
@endfor
