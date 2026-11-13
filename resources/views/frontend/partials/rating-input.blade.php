{{-- Clickable 1–5 star picker posting "rating". Optional $current. --}}
<div x-data="{ rating: {{ (int) ($current ?? 0) }}, hover: 0 }" class="flex items-center gap-1">
    <input type="hidden" name="rating" :value="rating || ''" required>
    @for($i = 1; $i <= 5; $i++)
        <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0"
                class="text-2xl leading-none transition-colors" :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-slate-300'"
                aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
            <i class="bi" :class="(hover || rating) >= {{ $i }} ? 'bi-star-fill' : 'bi-star'"></i>
        </button>
    @endfor
    <span class="ml-2 text-xs font-bold text-slate-400" x-text="rating ? rating + ' / 5' : '{{ __('Tap to rate') }}'"></span>
</div>
