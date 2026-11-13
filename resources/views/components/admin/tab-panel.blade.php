{{-- Panel for <x-admin.tabs>. data-tab lets forms jump to the tab holding an invalid field. --}}
@props([
    'name',
    'model' => 'tab',
])

<div role="tabpanel" id="panel-{{ $name }}" aria-labelledby="tab-{{ $name }}" data-tab="{{ $name }}" tabindex="0"
     x-show="{{ $model }} === '{{ $name }}'" x-cloak
     {{ $attributes->merge(['class' => 'focus:outline-none']) }}>
    {{ $slot }}
</div>
