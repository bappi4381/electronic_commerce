{{-- Toast stack for add-to-cart / wishlist feedback and session flashes. --}}
@php
    $flashes = collect(['success' => 'success', 'error' => 'error', 'warning' => 'error', 'info' => 'info'])
        ->map(fn ($type, $key) => is_string(session($key)) ? ['message' => session($key), 'type' => $type] : null)
        ->filter()->values();
@endphp

<div x-data x-init="@js($flashes).forEach(f => $store.toast.push(f.message, f.type))"
     class="pointer-events-none fixed inset-x-0 z-[var(--sf-z-toast)] flex flex-col items-center gap-2 px-4 bottom-[calc(var(--sf-bottom-nav-h)+var(--sf-safe-bottom)+12px)] md:bottom-auto md:top-24 md:items-end"
     aria-live="polite">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div x-transition.opacity class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-[var(--sf-radius)] bg-navy-900 px-4 py-3 text-sm text-white shadow-[var(--sf-shadow-lg)]"
             :role="toast.type === 'error' ? 'alert' : 'status'">
            <i class="bi text-lg" aria-hidden="true"
               :class="{ 'bi-check-circle-fill text-emerald-400': toast.type === 'success', 'bi-exclamation-circle-fill text-rose-400': toast.type === 'error', 'bi-info-circle-fill text-primary': toast.type === 'info' }"></i>
            <p class="flex-1" x-text="toast.message"></p>
            <template x-if="toast.action">
                <a :href="toast.action.url" class="shrink-0 rounded font-semibold text-primary-light hover:underline sf-focus" x-text="toast.action.label"></a>
            </template>
            <button type="button" @click="$store.toast.dismiss(toast.id)" class="-mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-white/60 hover:text-white sf-focus">
                <span class="sr-only">{{ __('Dismiss') }}</span><i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</div>
