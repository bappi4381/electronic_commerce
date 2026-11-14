{{-- Customer account shell: store header/footer, account navigation (sidebar on desktop, tabs on mobile). Pages fill @section('account'). --}}
@extends('frontend.layout')

@section('content')
@php
    $user = auth()->user();
    $unread = \App\Models\Message::where('user_id', $user->id)->where('is_admin', true)->where('is_read', false)->count();
    $nav = [
        ['route' => 'user.dashboard', 'active' => ['user.dashboard'], 'label' => __('Overview'), 'icon' => 'bi-grid', 'iconActive' => 'bi-grid-fill'],
        ['route' => 'user.orders.index', 'active' => ['user.orders.index', 'user.orders.details'], 'label' => __('My orders'), 'icon' => 'bi-bag', 'iconActive' => 'bi-bag-fill'],
        ['route' => 'user.orders.track', 'active' => ['user.orders.track', 'user.trackOrder.submit'], 'label' => __('Track order'), 'icon' => 'bi-truck', 'iconActive' => 'bi-truck-front-fill'],
        ['route' => 'wishlist.index', 'active' => ['wishlist.index'], 'label' => __('Wishlist'), 'icon' => 'bi-heart', 'iconActive' => 'bi-heart-fill'],
        ['route' => 'user.messages.index', 'active' => ['user.messages.index'], 'label' => __('Messages'), 'icon' => 'bi-chat-dots', 'iconActive' => 'bi-chat-dots-fill', 'badge' => $unread],
        ['route' => 'user.profile', 'active' => ['user.profile'], 'label' => __('Profile & security'), 'icon' => 'bi-person', 'iconActive' => 'bi-person-fill'],
    ];
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<div class="mx-auto max-w-7xl px-4 pb-12 pt-5 md:pb-16 md:pt-8">
    <div class="lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start lg:gap-8">
        {{-- Desktop sidebar --}}
        <aside class="hidden lg:sticky lg:top-24 lg:block" aria-label="{{ __('Account') }}">
            <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-4">
                <div class="flex items-center gap-3 border-b border-[var(--sf-border)] px-1 pb-4">
                    @if($user->avatar)
                        <img src="{{ \App\Support\Storefront::imageUrl($user->avatar) }}" alt="" class="size-12 shrink-0 rounded-full object-cover ring-2 ring-brand-50">
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-600 font-semibold text-white" aria-hidden="true">{{ $initials ?: 'U' }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>
                <nav class="mt-3">
                    <ul class="space-y-0.5">
                        @foreach($nav as $item)
                            @php($current = request()->routeIs(...$item['active']))
                            <li>
                                <a href="{{ route($item['route']) }}" @if($current) aria-current="page" @endif
                                   @class(['flex min-h-11 items-center gap-3 rounded-[var(--sf-radius-sm)] px-3 text-sm font-medium transition-colors sf-focus',
                                           'bg-brand-50 text-brand-700' => $current,
                                           'text-slate-700 hover:bg-slate-50 hover:text-slate-900' => !$current])>
                                    <i class="bi {{ $current ? $item['iconActive'] : $item['icon'] }} text-base" aria-hidden="true"></i>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if(!empty($item['badge']))
                                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-sale px-1.5 text-[11px] font-bold text-white">{{ localized_number($item['badge']) }}<span class="sr-only"> {{ __('unread') }}</span></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <form method="POST" action="{{ route('auth.logout') }}" class="mt-3 border-t border-[var(--sf-border)] pt-3">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-[var(--sf-radius-sm)] px-3 text-sm font-medium text-slate-600 hover:bg-rose-50 hover:text-rose-700 sf-focus">
                        <i class="bi bi-box-arrow-right text-base" aria-hidden="true"></i> {{ __('Log out') }}
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Mobile: account tabs --}}
            <nav class="-mx-4 mb-5 lg:hidden" aria-label="{{ __('Account') }}">
                <ul class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-1">
                    @foreach($nav as $item)
                        @php($current = request()->routeIs(...$item['active']))
                        <li class="shrink-0">
                            <a href="{{ route($item['route']) }}" @if($current) aria-current="page" @endif
                               @class(['flex min-h-10 items-center gap-1.5 rounded-full border px-4 text-sm font-medium sf-focus',
                                       'border-navy-900 bg-navy-900 text-white' => $current,
                                       'border-[var(--sf-border)] bg-white text-slate-700' => !$current])>
                                <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i> {{ $item['label'] }}
                                @if(!empty($item['badge']))
                                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-sale px-1.5 text-[11px] font-bold text-white">{{ localized_number($item['badge']) }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @foreach([
                'success' => ['border-emerald-200 bg-emerald-50 text-emerald-800', 'bi-check-circle-fill', 'status'],
                'error' => ['border-rose-200 bg-rose-50 text-rose-800', 'bi-exclamation-triangle-fill', 'alert'],
                'info' => ['border-sky-200 bg-sky-50 text-sky-800', 'bi-info-circle-fill', 'status'],
            ] as $key => [$tone, $icon, $role])
                @if(session($key))
                    <div class="mb-5 flex gap-3 rounded-[var(--sf-radius-sm)] border p-4 text-sm {{ $tone }}" role="{{ $role }}">
                        <i class="bi {{ $icon }} mt-0.5" aria-hidden="true"></i>
                        <p>{{ session($key) }}</p>
                    </div>
                @endif
            @endforeach

            @yield('account')
        </div>
    </div>
</div>
@endsection
