@php
    $about = \App\Models\Setting::get('footer_text', __('Genuine gadgets, home appliances and everyday essentials, delivered across Bangladesh with cash on delivery.'));
    $socials = collect([
        'facebook_url' => ['bi-facebook', 'Facebook'],
        'instagram_url' => ['bi-instagram', 'Instagram'],
        'twitter_url' => ['bi-twitter-x', 'X'],
    ])->map(fn ($meta, $key) => ['url' => \App\Models\Setting::get($key), 'icon' => $meta[0], 'label' => $meta[1]])->filter(fn ($s) => $s['url']);
    $sections = [
        'explore' => [__('Explore Store'), [
            [route('home'), __('Home')],
            [route('products.index'), __('All Products')],
            [route('flash-deals'), __('Deals')],
            [route('frontend.articles'), __('Tech Blog')],
        ]],
        'support' => [__('Customer Support'), array_merge(
            [[route('tracking.index'), __('Track My Order')]],
            \App\Models\Page::footerLinks()->map(fn ($page) => [route('pages.show', ['slug' => $page->slug]), $page->title])->all(),
            [[route('contact'), __('Contact Us')]],
        )],
    ];
    $address = \App\Models\Setting::get('contact_address');
    $phone = \App\Models\Setting::get('contact_phone');
    $email = \App\Models\Setting::get('contact_email');
@endphp

<footer class="bg-navy-950 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 pb-6 pt-10 md:pt-16">
        <div class="grid gap-2 md:grid-cols-2 md:gap-10 lg:grid-cols-4">
            <div class="mb-6 space-y-4 md:mb-0">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 rounded sf-focus">
                    @if($storeLogo)
                        <img src="{{ $storeLogo }}" alt="" class="h-9 w-auto" loading="lazy">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-[var(--sf-radius-sm)] bg-primary text-lg text-white" aria-hidden="true"><i class="bi bi-lightning-charge-fill"></i></span>
                    @endif
                    <span class="font-display text-xl font-extrabold uppercase tracking-tight text-white">{{ $storeName }}</span>
                </a>
                <p class="max-w-sm text-sm leading-relaxed text-slate-400">{{ $about }}</p>
                @if($socials->isNotEmpty())
                    <ul class="flex gap-2">
                        @foreach($socials as $social)
                            <li>
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="flex size-11 items-center justify-center rounded-full bg-white/5 text-lg text-slate-300 transition-colors hover:bg-primary hover:text-white sf-focus" aria-label="{{ $social['label'] }}">
                                    <i class="bi {{ $social['icon'] }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @foreach($sections as $key => [$title, $links])
                <div x-data="{ open: false }" class="border-t border-white/10 md:border-0">
                    <h2>
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="footer-{{ $key }}"
                                class="flex min-h-12 w-full items-center justify-between text-left text-sm font-semibold uppercase tracking-wider text-white md:pointer-events-none md:mb-3 md:min-h-0">
                            {{ $title }}
                            <i class="bi bi-chevron-down text-xs transition-transform md:hidden" :class="open && 'rotate-180'" aria-hidden="true"></i>
                        </button>
                    </h2>
                    <ul id="footer-{{ $key }}" class="space-y-1 pb-3 md:block md:space-y-2" :class="!open && 'max-md:hidden'">
                        @foreach($links as [$url, $label])
                            <li><a href="{{ $url }}" class="inline-flex min-h-9 items-center text-sm text-slate-400 hover:text-white sf-focus rounded">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div x-data="{ open: false }" class="border-y border-white/10 md:border-0">
                <h2>
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="footer-contact"
                            class="flex min-h-12 w-full items-center justify-between text-left text-sm font-semibold uppercase tracking-wider text-white md:pointer-events-none md:mb-3 md:min-h-0">
                        {{ __('Get In Touch') }}
                        <i class="bi bi-chevron-down text-xs transition-transform md:hidden" :class="open && 'rotate-180'" aria-hidden="true"></i>
                    </button>
                </h2>
                <ul id="footer-contact" class="space-y-3 pb-4 text-sm md:block" :class="!open && 'max-md:hidden'">
                    @if($address)
                        <li class="flex gap-3"><i class="bi bi-geo-alt mt-0.5 text-primary" aria-hidden="true"></i><span class="text-slate-400">{!! nl2br(e($address)) !!}</span></li>
                    @endif
                    @if($phone)
                        <li class="flex gap-3"><i class="bi bi-telephone mt-0.5 text-primary" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-slate-400 hover:text-white">{{ $phone }}</a></li>
                    @endif
                    @if($email)
                        <li class="flex gap-3"><i class="bi bi-envelope mt-0.5 text-primary" aria-hidden="true"></i><a href="mailto:{{ $email }}" class="break-all text-slate-400 hover:text-white">{{ $email }}</a></li>
                    @endif
                    <li class="flex gap-3"><i class="bi bi-chat-dots mt-0.5 text-primary" aria-hidden="true"></i><button type="button" @click="$dispatch('open-chat')" class="text-left text-slate-400 hover:text-white">{{ __('Live chat, 24/7') }}</button></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-5 border-t border-white/10 pt-6 md:flex-row md:items-center md:justify-between">
            <ul class="flex flex-wrap items-center gap-2" aria-label="{{ __('Payment methods') }}">
                <li class="flex h-8 items-center rounded-md bg-[#e2136e] px-2.5 text-xs font-bold text-white">bKash</li>
                <li class="flex h-8 items-center rounded-md bg-[#ec1c24] px-2.5 text-xs font-bold text-white">Nagad</li>
                <li class="flex h-8 items-center rounded-md bg-white px-2.5 text-xs font-extrabold italic tracking-tight text-[#1a1f71]">VISA</li>
                <li class="flex h-8 items-center gap-1 rounded-md bg-white px-2.5" aria-label="Mastercard">
                    <svg width="28" height="18" viewBox="0 0 28 18" aria-hidden="true"><circle cx="10" cy="9" r="8" fill="#eb001b"/><circle cx="18" cy="9" r="8" fill="#f79e1b" fill-opacity=".9"/></svg>
                </li>
                <li class="flex h-8 items-center gap-1.5 rounded-md bg-white/10 px-2.5 text-xs font-semibold text-white"><i class="bi bi-cash-coin" aria-hidden="true"></i> {{ __('Cash on delivery') }}</li>
            </ul>
            <p class="text-xs text-slate-500">© {{ date('Y') }} {{ $storeName }} · {{ __('Digital Universe') }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>
</footer>
