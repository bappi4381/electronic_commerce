@extends('user.layout')

@section('title', __('Profile & security'))

@section('account')
@php
    $zones = array_keys(app(\App\Services\CheckoutOptions::class)->cityOptions());
    $currentCity = old('city', $user->city);
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $selectClass = 'block h-[52px] w-full cursor-pointer appearance-none rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white pb-1.5 pl-3 pr-9 pt-5 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25';
@endphp

<form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
      x-data="{ preview: null, saving: false, pwOpen: @js($errors->hasAny(['password', 'current_password'])), emailChanged: @js(old('email', $user->email) !== $user->email) }" @submit="saving = true">
    @csrf
    @method('PUT')

    <header>
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Profile & security') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Keep your contact details and default delivery address up to date for faster checkout.') }}</p>
    </header>

    @if($errors->any())
        <div class="flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ __('Please check the highlighted fields and try again.') }}</p>
        </div>
    @endif

    {{-- Photo --}}
    <section class="{{ $card }} flex flex-wrap items-center gap-5" aria-labelledby="photo-title">
        <div class="relative">
            <template x-if="preview"><img :src="preview" alt="" class="size-20 rounded-full object-cover ring-4 ring-brand-50"></template>
            <div x-show="!preview">
                @if($user->avatar)
                    <img src="{{ \App\Support\Storefront::imageUrl($user->avatar) }}" alt="" class="size-20 rounded-full object-cover ring-4 ring-brand-50">
                @else
                    <span class="flex size-20 items-center justify-center rounded-full bg-brand-600 text-2xl font-semibold text-white ring-4 ring-brand-50" aria-hidden="true">{{ $initials ?: 'U' }}</span>
                @endif
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <h2 id="photo-title" class="font-semibold text-slate-900">{{ $user->name }}</h2>
            <p class="text-sm text-slate-500">{{ __('Customer ID') }} <span class="font-mono">{{ $user->user_id }}</span> · {{ __('Member since :date', ['date' => $user->created_at->format('M Y')]) }}</p>
            <label for="avatar" class="mt-3 inline-flex h-10 cursor-pointer items-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 focus-within:outline-2 focus-within:outline-primary">
                <i class="bi bi-camera" aria-hidden="true"></i> {{ __('Change photo') }}
                <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only"
                       @change="const file = $event.target.files[0]; preview = file ? URL.createObjectURL(file) : null">
            </label>
            <p class="mt-1.5 text-xs text-slate-500">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</p>
            @error('avatar')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>

    {{-- Personal details --}}
    <section class="{{ $card }}" aria-labelledby="personal-title">
        <h2 id="personal-title" class="text-lg font-semibold text-slate-900">{{ __('Personal details') }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <x-checkout.field name="name" :label="__('Full name')" :value="$user->name" required autocomplete="name" maxlength="255" class="md:col-span-2" />
            <div>
                <x-checkout.field name="email" type="email" :label="__('Email')" :value="$user->email" required autocomplete="email" maxlength="255" x-on:input="emailChanged = $event.target.value.trim().toLowerCase() !== @js(strtolower($user->email))" />
                @if($user->is_email_verified)
                    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-emerald-700"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> {{ __('Verified email') }}</p>
                @endif
            </div>
            <x-checkout.field name="phone" type="tel" :label="__('Mobile number')" :value="$user->phone" autocomplete="tel" inputmode="tel" maxlength="20" />
        </div>
    </section>

    {{-- Default address --}}
    <section class="{{ $card }}" aria-labelledby="address-title">
        <h2 id="address-title" class="text-lg font-semibold text-slate-900">{{ __('Default delivery address') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('We fill this in for you at checkout.') }}</p>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="md:col-span-3">
                <label for="address" class="sr-only">{{ __('Address') }}</label>
                <textarea id="address" name="address" rows="2" maxlength="255" autocomplete="street-address" placeholder="{{ __('House, road, area, apartment, landmark') }}"
                          @class(['block w-full resize-y rounded-[var(--sf-radius-sm)] border px-3 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2',
                                  'border-rose-500 focus:ring-rose-500/20' => $errors->has('address'),
                                  'border-slate-300 focus:border-brand-600 focus:ring-primary/25' => !$errors->has('address')])>{{ old('address', $user->address) }}</textarea>
                @error('address')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <div class="relative">
                    <select id="city" name="city" class="{{ $selectClass }}" autocomplete="address-level2">
                        <option value="">{{ __('Select') }}</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone }}" @selected($currentCity === $zone)>{{ $zone === \App\Services\CheckoutOptions::OTHER_ZONE ? __('Other areas') : $zone }}</option>
                        @endforeach
                        @if($currentCity && !in_array($currentCity, $zones, true))
                            <option value="{{ $currentCity }}" selected>{{ $currentCity }}</option>
                        @endif
                    </select>
                    <label for="city" class="pointer-events-none absolute left-3 top-2 text-xs text-slate-500">{{ __('City / zone') }}</label>
                    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500" aria-hidden="true"></i>
                </div>
                @error('city')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <x-checkout.field name="state" :label="__('Area / thana')" :value="$user->state" autocomplete="address-level3" maxlength="100" />
            <x-checkout.field name="postal_code" :label="__('Postcode')" :value="$user->postal_code" autocomplete="postal-code" inputmode="numeric" maxlength="20" />
        </div>
    </section>

    {{-- Password --}}
    <section class="{{ $card }}" aria-labelledby="password-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="password-title" class="text-lg font-semibold text-slate-900">{{ __('Password') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $user->password ? __('Change the password you use to log in.') : __('You signed up with :provider. Set a password to also log in with your email.', ['provider' => ucfirst((string) $user->provider)]) }}</p>
            </div>
            <button type="button" x-show="!pwOpen" @click="pwOpen = true; $nextTick(() => $refs.pw.querySelector('input').focus())"
                    class="flex h-10 items-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                <i class="bi bi-key" aria-hidden="true"></i> {{ $user->password ? __('Change password') : __('Set password') }}
            </button>
        </div>
        <div x-show="pwOpen" x-ref="pw" @if(!$errors->hasAny(['password', 'current_password'])) style="display: none" @endif class="mt-4 grid gap-3 md:grid-cols-2">
            <x-auth.password name="password" :label="__('New password')" autocomplete="new-password" :hint="__('At least 8 characters, with letters and numbers.')" />
            <x-auth.password name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" />
        </div>
    </section>

    @if($user->hasPassword())
        <section class="{{ $card }} border-amber-200 bg-amber-50/40" aria-labelledby="confirm-title" x-show="pwOpen || emailChanged"
                 @if(!$errors->has('current_password') && old('email', $user->email) === $user->email) style="display: none" @endif>
            <h2 id="confirm-title" class="flex items-center gap-2 text-base font-semibold text-slate-900"><i class="bi bi-shield-lock text-amber-600" aria-hidden="true"></i> {{ __('Confirm it’s you') }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ __('Enter your current password to change your email or password.') }}</p>
            <div class="mt-3 md:max-w-sm">
                <x-auth.password name="current_password" :label="__('Current password')" autocomplete="current-password" />
            </div>
        </section>
    @endif

    <div class="flex flex-col-reverse items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-xs text-slate-500">{{ __('Last updated :time', ['time' => $user->updated_at->diffForHumans()]) }}</p>
        <button type="submit" :disabled="saving"
                class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-8 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-70 sm:w-auto sf-focus">
            <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'" aria-hidden="true"></i> {{ __('Save changes') }}
        </button>
    </div>
</form>
@endsection
