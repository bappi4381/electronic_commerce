{{-- Cart → Details & payment → Confirmation. current: cart | details | done --}}
@props(['current' => 'cart'])

@php
    $steps = [
        'cart' => ['label' => __('Cart'), 'icon' => 'bi-bag', 'url' => route('cart.index')],
        'details' => ['label' => __('Details & payment'), 'icon' => 'bi-person-vcard', 'url' => null],
        'done' => ['label' => __('Confirmation'), 'icon' => 'bi-check2-circle', 'url' => null],
    ];
    $keys = array_keys($steps);
    $currentIndex = array_search($current, $keys, true);
@endphp

<nav aria-label="{{ __('Checkout steps') }}" {{ $attributes }}>
    <ol class="flex items-center">
        @foreach($steps as $key => $step)
            @php
                $index = $loop->index;
                $complete = $index < $currentIndex;
                $active = $index === $currentIndex;
                $tag = $complete && $step['url'] ? 'a' : 'span';
            @endphp
            <li @class(['flex items-center', 'flex-1' => !$loop->last])>
                <{{ $tag }} @if($tag === 'a') href="{{ $step['url'] }}" @endif @if($active) aria-current="step" @endif
                    @class(['flex items-center gap-2 rounded-full text-xs font-medium sm:text-sm', 'sf-focus hover:text-brand-700' => $tag === 'a',
                            'text-slate-900' => $active, 'text-brand-700' => $complete, 'text-slate-400' => !$active && !$complete])>
                    <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-xs sm:size-8',
                                  'bg-navy-900 text-white' => $active,
                                  'bg-brand-600 text-white' => $complete,
                                  'border border-slate-300 bg-white text-slate-400' => !$active && !$complete]) aria-hidden="true">
                        <i class="bi {{ $complete ? 'bi-check-lg' : $step['icon'] }}"></i>
                    </span>
                    <span @class(['hidden sm:inline' => !$active, 'font-semibold' => $active])>{{ $step['label'] }}</span>
                </{{ $tag }}>
                @unless($loop->last)
                    <span @class(['mx-2 h-px flex-1 sm:mx-3', 'bg-brand-600' => $complete, 'bg-slate-300' => !$complete]) aria-hidden="true"></span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
