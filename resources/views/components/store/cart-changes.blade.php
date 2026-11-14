{{-- What changed in the cart since the customer last saw it (prices, stock, removed items). --}}
@props(['changes' => []])

@if($changes)
    <div {{ $attributes->merge(['class' => 'flex gap-3 rounded-[var(--sf-radius-sm)] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900']) }} role="status">
        <i class="bi bi-info-circle-fill mt-0.5 text-amber-600" aria-hidden="true"></i>
        <div>
            <p class="font-semibold">{{ __('Your cart was updated') }}</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach($changes as $change)
                    <li>{{ $change }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
