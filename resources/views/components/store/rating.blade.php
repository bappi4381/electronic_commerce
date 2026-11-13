@props(['value' => 0, 'count' => 0])

@if($count > 0)
    @php($value = round((float) $value * 2) / 2)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1 text-xs text-slate-500']) }}>
        <span class="flex text-amber-400" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                <i class="bi {{ $value >= $i ? 'bi-star-fill' : ($value >= $i - 0.5 ? 'bi-star-half' : 'bi-star') }} text-[11px]"></i>
            @endfor
        </span>
        <span class="sr-only">{{ __(':rating out of 5 stars', ['rating' => number_format($value, 1)]) }},</span>
        <span class="tabular-nums">({{ localized_number($count) }})</span>
    </div>
@endif
