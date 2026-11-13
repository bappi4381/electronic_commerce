{{--
    Accessible tab list bound to an Alpine variable in the parent scope (default "tab").
    Pair with <x-admin.tab-panel name="…">. :tabs is key => label, or key => ['label' => …, 'icon' => …].
    :errors is key => bool to flag tabs that contain validation errors.
--}}
@props([
    'tabs',
    'model' => 'tab',
    'errors' => [],
    'label' => 'Sections',
])

@php($errors = is_array($errors) ? $errors : [])

<div role="tablist" aria-label="{{ $label }}" x-data="tabList"
     @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
     {{ $attributes->merge(['class' => 'scrollbar-thin flex gap-1 overflow-x-auto border-b border-gray-200 dark:border-gray-800']) }}>
    @foreach($tabs as $key => $tab)
        @php($tab = is_array($tab) ? $tab : ['label' => $tab])
        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                @click="{{ $model }} = '{{ $key }}'"
                :aria-selected="({{ $model }} === '{{ $key }}').toString()" :tabindex="{{ $model }} === '{{ $key }}' ? 0 : -1"
                class="relative -mb-px flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors focus-ring"
                :class="{{ $model }} === '{{ $key }}' ? 'border-brand-600 text-brand-700 dark:border-brand-400 dark:text-brand-300' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
            @isset($tab['icon'])<i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>@endisset
            {{ $tab['label'] }}
            @if(!empty($errors[$key]))
                <span class="size-2 rounded-full bg-red-500" aria-hidden="true"></span><span class="sr-only">(has errors)</span>
            @endif
        </button>
    @endforeach
</div>
