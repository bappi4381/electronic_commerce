@extends('frontend.layout')

@php
    $storeName = \App\Models\Setting::get('site_name', 'ONEMALL');
    $topics = collect(\App\Http\Controllers\Frontend\ContactController::TOPICS)
        ->mapWithKeys(fn ($topic) => [$topic => __(\App\Http\Controllers\Frontend\ContactController::topicLabel($topic))]);
    $selectedTopic = old('topic', request('topic', 'order'));
    $telLink = $contact['phone'] ? 'tel:' . preg_replace('/[^\d+]/', '', $contact['phone']) : null;
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white';
@endphp

@section('title', __('Contact us'))
@section('meta_description', __('Questions about an order, a product or warranty? Contact :store by phone, email or message — we reply within one working day.', ['store' => $storeName]))

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-12 pt-4 md:pb-16 md:pt-6">
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li aria-current="page" class="font-medium text-slate-700">{{ __('Contact us') }}</li>
        </ol>
    </nav>

    <header class="mt-3 max-w-2xl md:mt-4">
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ __('How can we help?') }}</h1>
        <p class="mt-2 text-sm text-slate-600 md:text-base">{{ __('Questions about an order, a product or a warranty claim? Send us a message and our team replies within one working day.') }}</p>
    </header>

    {{-- Quick channels --}}
    <ul class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @if($contact['phone'])
            <li>
                <a href="{{ $telLink }}" class="{{ $card }} group flex h-full flex-col items-start gap-2 p-3 sm:flex-row sm:gap-3 sm:p-4 transition-shadow hover:shadow-[var(--sf-shadow)] sf-focus">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true"><i class="bi bi-telephone-fill"></i></span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-slate-500">{{ __('Call us') }}</span>
                        <span class="block truncate font-semibold text-slate-900 group-hover:text-brand-700">{{ $contact['phone'] }}</span>
                        <span class="hidden text-xs text-slate-500 sm:block">{{ __('Fastest for urgent order questions') }}</span>
                    </span>
                </a>
            </li>
        @endif
        @if($contact['email'])
            <li>
                <a href="mailto:{{ $contact['email'] }}" class="{{ $card }} group flex h-full flex-col items-start gap-2 p-3 sm:flex-row sm:gap-3 sm:p-4 transition-shadow hover:shadow-[var(--sf-shadow)] sf-focus">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700" aria-hidden="true"><i class="bi bi-envelope-fill"></i></span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-slate-500">{{ __('Email') }}</span>
                        <span class="block truncate font-semibold text-slate-900 group-hover:text-brand-700">{{ $contact['email'] }}</span>
                        <span class="hidden text-xs text-slate-500 sm:block">{{ __('Reply within one working day') }}</span>
                    </span>
                </a>
            </li>
        @endif
        <li>
            <button type="button" @click="window.dispatchEvent(new CustomEvent('open-chat'))"
                    class="{{ $card }} group flex h-full w-full flex-col items-start gap-2 p-3 sm:flex-row sm:gap-3 sm:p-4 text-left transition-shadow hover:shadow-[var(--sf-shadow)] sf-focus">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700" aria-hidden="true"><i class="bi bi-chat-dots-fill"></i></span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-slate-500">{{ __('Live chat') }}</span>
                    <span class="block font-semibold text-slate-900 group-hover:text-brand-700">{{ __('Chat with us') }}</span>
                    <span class="hidden text-xs text-slate-500 sm:block">{{ __('Quick answers while you shop') }}</span>
                </span>
            </button>
        </li>
        <li>
            <a href="{{ route('tracking.index') }}" class="{{ $card }} group flex h-full flex-col items-start gap-2 p-3 sm:flex-row sm:gap-3 sm:p-4 transition-shadow hover:shadow-[var(--sf-shadow)] sf-focus">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700" aria-hidden="true"><i class="bi bi-truck"></i></span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-slate-500">{{ __('Where is my order?') }}</span>
                    <span class="block font-semibold text-slate-900 group-hover:text-brand-700">{{ __('Track your order') }}</span>
                    <span class="hidden text-xs text-slate-500 sm:block">{{ __('Live status with your order ID') }}</span>
                </span>
            </a>
        </li>
    </ul>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-8">
        {{-- Form --}}
        <section class="{{ $card }} p-5 md:p-8" aria-labelledby="contact-form-title">
            @if(session('contact_sent'))
                <div class="flex flex-col items-center py-8 text-center" role="status">
                    <span class="flex size-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                    <h2 id="contact-form-title" class="mt-4 font-display text-xl font-bold text-slate-900">{{ __('Message sent — thank you!') }}</h2>
                    <p class="mt-2 max-w-md text-sm text-slate-600">{{ __('Our team will reply within one working day by phone or email. Replies also appear in the chat bubble on this site.') }}</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('products.index') }}" class="flex h-11 items-center rounded-[var(--sf-radius-sm)] bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">{{ __('Continue shopping') }}</a>
                        <a href="{{ route('contact') }}" class="flex h-11 items-center rounded-[var(--sf-radius-sm)] border border-slate-300 px-5 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">{{ __('Send another message') }}</a>
                    </div>
                </div>
            @else
                <h2 id="contact-form-title" class="font-display text-lg font-bold text-slate-900 md:text-xl">{{ __('Send us a message') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Give us a phone number or an email address so we can reply.') }}</p>

                @if($errors->any())
                    <div class="mt-4 flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ __('Please check the highlighted fields and try again.') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" class="mt-5 space-y-4" x-data="{ topic: @js($selectedTopic), sending: false }" @submit="sending = true">
                    @csrf
                    {{-- Bots fill every field; people never see this one. --}}
                    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-slate-800">{{ __('What is it about?') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach($topics as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="topic" value="{{ $value }}" x-model="topic" class="peer sr-only" @checked($selectedTopic === $value)>
                                    <span class="inline-flex min-h-10 items-center rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-700 transition-colors peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 hover:border-slate-400">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('topic')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </fieldset>

                    <x-checkout.field name="name" :label="__('Your name')" :value="$prefill['name']" required autocomplete="name" maxlength="100" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-checkout.field name="phone" type="tel" :label="__('Mobile number')" :value="$prefill['phone']" autocomplete="tel" inputmode="tel" maxlength="20" />
                        <x-checkout.field name="email" type="email" :label="__('Email')" :value="$prefill['email']" autocomplete="email" maxlength="150" />
                    </div>
                    <div x-show="['order', 'return', 'warranty'].includes(topic)" x-cloak>
                        <x-checkout.field name="order_id" :label="__('Order ID')" optional maxlength="40" placeholder=" " :hint="__('Starts with ORD-, shown in your order confirmation.')" />
                    </div>

                    <div>
                        <label for="contact-message" class="mb-1.5 block text-sm font-medium text-slate-800">{{ __('Message') }}</label>
                        <textarea id="contact-message" name="message" rows="6" required minlength="10" maxlength="2000"
                                  placeholder="{{ __('Tell us what happened or what you would like to know…') }}"
                                  @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
                                  class="block w-full rounded-[var(--sf-radius-sm)] border bg-white px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 {{ $errors->has('message') ? 'border-rose-500 focus:ring-rose-500/20' : 'border-slate-300 focus:border-brand-600 focus:ring-primary/25' }}">{{ old('message') }}</textarea>
                        @error('message')<p id="contact-message-error" class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500"><i class="bi bi-shield-lock" aria-hidden="true"></i> {{ __('We only use your details to answer this message.') }}</p>
                        <button type="submit" :disabled="sending"
                                class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:opacity-70 sf-focus">
                            <i class="bi" :class="sending ? 'bi-arrow-repeat animate-spin' : 'bi-send-fill'" aria-hidden="true"></i>
                            <span x-text="sending ? @js(__('Sending…')) : @js(__('Send message'))">{{ __('Send message') }}</span>
                        </button>
                    </div>
                </form>
            @endif
        </section>

        {{-- Store details --}}
        <aside class="space-y-4">
            <section class="{{ $card }} p-5" aria-labelledby="visit-title">
                <h2 id="visit-title" class="font-display text-base font-bold text-slate-900">{{ __('Visit or write to us') }}</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    @if($contact['address'])
                        <div class="flex gap-3">
                            <dt><span class="sr-only">{{ __('Address') }}</span><i class="bi bi-geo-alt-fill text-brand-600" aria-hidden="true"></i></dt>
                            <dd class="whitespace-pre-line text-slate-700">{{ $contact['address'] }}</dd>
                        </div>
                    @endif
                    @if($contact['phone'])
                        <div class="flex gap-3">
                            <dt><span class="sr-only">{{ __('Phone') }}</span><i class="bi bi-telephone-fill text-brand-600" aria-hidden="true"></i></dt>
                            <dd><a href="{{ $telLink }}" class="rounded font-medium text-slate-900 hover:text-brand-700 sf-focus">{{ $contact['phone'] }}</a></dd>
                        </div>
                    @endif
                    @if($contact['email'])
                        <div class="flex gap-3">
                            <dt><span class="sr-only">{{ __('Email') }}</span><i class="bi bi-envelope-fill text-brand-600" aria-hidden="true"></i></dt>
                            <dd><a href="mailto:{{ $contact['email'] }}" class="break-all rounded font-medium text-slate-900 hover:text-brand-700 sf-focus">{{ $contact['email'] }}</a></dd>
                        </div>
                    @endif
                    <div class="flex gap-3">
                        <dt><span class="sr-only">{{ __('Hours') }}</span><i class="bi bi-clock-fill text-brand-600" aria-hidden="true"></i></dt>
                        <dd class="text-slate-700">{{ __('Messages answered every day, 10 AM – 8 PM') }}</dd>
                    </div>
                </dl>
                @if($socials->isNotEmpty())
                    <div class="mt-5 flex gap-2 border-t border-[var(--sf-border)] pt-4">
                        @foreach($socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}"
                               class="flex size-10 items-center justify-center rounded-full border border-[var(--sf-border)] text-slate-600 hover:border-brand-600 hover:text-brand-700 sf-focus">
                                <i class="bi {{ $social['icon'] }}" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            @if($contact['address'])
                <div class="{{ $card }} overflow-hidden">
                    <iframe title="{{ __('Map showing our address') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q={{ urlencode(str_replace("\n", ', ', $contact['address'])) }}&z=15&output=embed"
                            class="block h-56 w-full border-0"></iframe>
                </div>
            @endif

            @if($helpPages->isNotEmpty())
                <section class="{{ $card }} p-5" aria-labelledby="help-title">
                    <h2 id="help-title" class="font-display text-base font-bold text-slate-900">{{ __('Quick answers') }}</h2>
                    <ul class="mt-3 divide-y divide-[var(--sf-border)]">
                        @foreach($helpPages as $page)
                            <li>
                                <a href="{{ route('pages.show', $page->slug) }}" class="flex min-h-11 items-center justify-between gap-3 rounded text-sm font-medium text-slate-700 hover:text-brand-700 sf-focus">
                                    {{ $page->title }} <i class="bi bi-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
