<form action="{{ route('cart.update', $id) }}" method="POST" class="inline-flex h-10 items-center rounded-full border border-slate-300 bg-white">
    @csrf
    <button type="submit" name="quantity" value="{{ $quantity - 1 }}" @disabled($quantity <= 1)
            class="flex size-10 items-center justify-center rounded-full text-slate-600 hover:text-brand-700 disabled:cursor-not-allowed disabled:text-slate-300 sf-focus">
        <span class="sr-only">{{ __('Decrease quantity of :name', ['name' => $name]) }}</span><i class="bi bi-dash-lg" aria-hidden="true"></i>
    </button>
    <span class="w-8 text-center text-sm font-semibold tabular-nums text-slate-900" aria-live="polite">
        <span class="sr-only">{{ __('Quantity') }}:</span>{{ localized_number($quantity) }}
    </span>
    <button type="submit" name="quantity" value="{{ $quantity + 1 }}" @disabled($stock !== null && $quantity >= $stock)
            class="flex size-10 items-center justify-center rounded-full text-slate-600 hover:text-brand-700 disabled:cursor-not-allowed disabled:text-slate-300 sf-focus">
        <span class="sr-only">{{ __('Increase quantity of :name', ['name' => $name]) }}</span><i class="bi bi-plus-lg" aria-hidden="true"></i>
    </button>
</form>
