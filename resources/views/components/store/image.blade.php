{{--
    Image with a shimmer while loading and a neutral category-icon placeholder when the image is
    missing or fails, so a broken image or its alt text is never shown.
--}}
@props([
    'src' => null,
    'alt' => '',
    'width' => 400,
    'height' => 400,
    'icon' => 'bi-box-seam',
    'fit' => 'contain',
    'eager' => false,
    'imgClass' => '',
])

<div {{ $attributes->merge(['class' => 'sf-img relative overflow-hidden']) }} data-state="{{ $src ? 'loading' : 'error' }}">
    <span class="sf-img-fallback absolute inset-0 flex items-center justify-center text-slate-300 transition-opacity" aria-hidden="true">
        <i class="bi {{ $icon }} text-[2.5rem]"></i>
    </span>
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if($eager) fetchpriority="high" @endif
             onload="this.parentNode.dataset.state='loaded'"
             onerror="this.parentNode.dataset.state='error';this.remove()"
             class="relative size-full {{ $fit === 'cover' ? 'object-cover' : 'object-contain' }} {{ $imgClass }}">
    @endif
</div>
