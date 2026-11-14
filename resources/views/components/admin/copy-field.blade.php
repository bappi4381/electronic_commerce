{{-- Read-only value with a copy button; secret values stay masked until "Show" is pressed. --}}
@props([
    'label',
    'value' => null,
    'secret' => false,
    'empty' => 'Not set',
])

@php($id = 'copy_' . \Illuminate\Support\Str::random(6))

<div x-data="{ shown: @js(!$secret), copied: false }" {{ $attributes }}>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @if(filled($value))
        <div class="flex gap-2">
            <input id="{{ $id }}" readonly :type="shown ? 'text' : 'password'" value="{{ $value }}" @focus="$el.select()"
                   class="block h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 font-mono text-xs text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
            @if($secret)
                <button type="button" @click="shown = !shown" x-text="shown ? 'Hide' : 'Show'"
                        class="h-10 shrink-0 rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 focus-ring dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Show</button>
            @endif
            <button type="button" @click="navigator.clipboard?.writeText(@js($value)); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 focus-ring dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                <i class="bi" :class="copied ? 'bi-check2 text-green-600' : 'bi-clipboard'" aria-hidden="true"></i>
                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
            </button>
        </div>
    @else
        <p id="{{ $id }}" class="flex h-10 items-center rounded-lg border border-dashed border-gray-300 px-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ $empty }}</p>
    @endif
</div>
