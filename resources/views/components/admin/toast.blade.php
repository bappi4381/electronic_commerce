{{--
    Toast stack (rendered once by the layout). Session flashes (success, error, warning, info, status) and a
    validation summary are queued on load; JS can add more with toast('Saved', 'success').
    :flash="false" skips the session flashes (pages that still render their own alerts).
--}}
@props(['flash' => true])

@php
    $flashes = !$flash ? collect() : collect(['success' => 'success', 'status' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'])
        ->map(fn ($type, $key) => session()->has($key) && is_string(session($key)) ? ['message' => session($key), 'type' => $type] : null)
        ->filter()
        ->values();

    if ($flash && $errors->any()) {
        $flashes->push(['message' => $errors->count() === 1 ? $errors->first() : 'Please fix the ' . $errors->count() . ' highlighted problems.', 'type' => 'error']);
    }
@endphp

<div x-data x-init="@js($flashes).forEach(f => $store.toasts.push(f.message, f.type, f.type === 'error' ? 8000 : 5000))"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[70] flex flex-col items-center gap-2 p-4 sm:bottom-auto sm:top-4 sm:items-end"
     aria-live="polite" aria-atomic="false">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-800 dark:bg-gray-900">
            <i class="bi mt-0.5 text-lg" aria-hidden="true" :class="{
                'bi-check-circle-fill text-green-600 dark:text-green-400': toast.type === 'success',
                'bi-x-circle-fill text-red-600 dark:text-red-400': toast.type === 'error',
                'bi-exclamation-triangle-fill text-yellow-500': toast.type === 'warning',
                'bi-info-circle-fill text-brand-600 dark:text-brand-400': toast.type === 'info',
            }"></i>
            <p class="flex-1 text-sm text-gray-800 dark:text-gray-200" x-text="toast.message"></p>
            <button type="button" @click="$store.toasts.dismiss(toast.id)" class="-m-1 rounded p-1 text-gray-400 hover:text-gray-600 focus-ring dark:hover:text-gray-200">
                <span class="sr-only">Dismiss</span><i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</div>
