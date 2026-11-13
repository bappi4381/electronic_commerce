{{-- Loading placeholder. type: text | avatar | card | table | stat --}}
@props([
    'type' => 'text',
    'lines' => 3,
    'rows' => 5,
])

@php($bar = 'rounded bg-gray-200 dark:bg-gray-800')

<div role="status" aria-live="polite" {{ $attributes->merge(['class' => 'animate-pulse']) }}>
    <span class="sr-only">Loading…</span>
    @switch($type)
        @case('avatar')
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                <div class="flex-1 space-y-2"><div class="{{ $bar }} h-3 w-1/3"></div><div class="{{ $bar }} h-3 w-1/2"></div></div>
            </div>
            @break
        @case('stat')
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="{{ $bar }} h-3 w-24"></div>
                <div class="{{ $bar }} mt-4 h-7 w-32"></div>
                <div class="{{ $bar }} mt-4 h-3 w-20"></div>
            </div>
            @break
        @case('card')
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="{{ $bar }} h-4 w-40"></div>
                <div class="mt-6 space-y-3">
                    @for($i = 0; $i < $lines; $i++)<div class="{{ $bar }} h-3" style="width: {{ 100 - ($i % 3) * 15 }}%"></div>@endfor
                </div>
            </div>
            @break
        @case('table')
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @for($i = 0; $i < $rows; $i++)
                    <div class="flex items-center gap-4 py-4">
                        <div class="size-9 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                        <div class="{{ $bar }} h-3 flex-1"></div>
                        <div class="{{ $bar }} h-3 w-24"></div>
                        <div class="{{ $bar }} h-3 w-16"></div>
                    </div>
                @endfor
            </div>
            @break
        @default
            <div class="space-y-3">
                @for($i = 0; $i < $lines; $i++)<div class="{{ $bar }} h-3" style="width: {{ 100 - ($i % 3) * 15 }}%"></div>@endfor
            </div>
    @endswitch
</div>
