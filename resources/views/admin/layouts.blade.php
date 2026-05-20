<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | ONEMALL</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Tailwind CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Quill Editor CSS --}}
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .sidebar-scroll:hover::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="bg-slate-50 font-sans text-slate-900" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

    {{-- Mobile Sidebar Overlay --}}
    <div x-show="mobileSidebarOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="mobileSidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'w-72' : 'w-20'"
        class="fixed top-0 left-0 z-50 h-screen bg-slate-900 text-white transition-all duration-300 ease-in-out sidebar-scroll overflow-y-auto hidden lg:flex flex-col border-r border-white/5">

        <!-- Sidebar Header -->
        <div class="flex items-center gap-4 px-6 py-8 h-24 flex-shrink-0">
            <div
                class="bg-primary text-white w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-primary/20 flex-shrink-0">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="flex flex-col transition-opacity duration-300"
                :class="sidebarOpen ? 'opacity-100' : 'opacity-0 invisible'">
                <h1 class="m-0 text-xl font-black tracking-tighter leading-none uppercase italic">ONEMALL</h1>
                <span class="text-[8px] text-primary-light font-black tracking-[0.2em] uppercase mt-1">Admin
                    Central</span>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-grow px-4 pb-8 space-y-6 mt-4">
            @php
                $currentRoute = Route::currentRouteName();
                $menus = [
                    ['title' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'route' => 'admin.dashboard'],
                    [
                        'title' => 'Inventory',
                        'icon' => 'bi-box-seam',
                        'children' => [
                            ['title' => 'Categories', 'route' => 'category_subcategory.index'],
                            ['title' => 'Add Product', 'route' => 'admin.products.create'],
                            ['title' => 'Products', 'route' => 'admin.products.index'],
                        ]
                    ],
                    [
                        'title' => 'Orders',
                        'icon' => 'bi-bag-check',
                        'children' => [
                            ['title' => 'Order List', 'route' => 'orders.index'],
                            ['title' => 'New Order', 'route' => 'orders.create'],
                        ]
                    ],
                    [
                        'title' => 'Customers',
                        'icon' => 'bi-people',
                        'route' => 'users.index'
                    ],
                    [
                        'title' => 'Payments',
                        'icon' => 'bi-credit-card',
                        'route' => 'payments.index'
                    ],
                    [
                        'title' => 'Banners',
                        'icon' => 'bi-images',
                        'route' => 'banners.index'
                    ],
                    [
                        'title' => 'Tech Blog',
                        'icon' => 'bi-journal-richtext',
                        'children' => [
                            ['title' => 'All Stories', 'route' => 'articles.index'],
                            ['title' => 'Write New', 'route' => 'articles.create'],
                        ]
                    ],
                    [
                        'title' => 'Marketing',
                        'icon' => 'bi-megaphone',
                        'route' => 'marketing.index'
                    ],
                    [
                        'title' => 'Messages',
                        'icon' => 'bi-chat-dots',
                        'route' => 'admin.messages.index'
                    ],
                    ['title' => 'Global Settings', 'icon' => 'bi-gear', 'route' => 'admin.settings.index'],
                ];
            @endphp
            @foreach ($menus as $menu)
                @if (isset($menu['children']))
                    @php
                        $childRoutes = array_column($menu['children'], 'route');
                        $isActive = in_array($currentRoute, $childRoutes);
                    @endphp
                    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 group {{ $isActive ? 'bg-white/5 text-primary' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <i class="bi {{ $menu['icon'] }} text-lg"></i>
                                <span class="transition-opacity duration-300"
                                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 invisible'">{{ $menu['title'] }}</span>
                            </div>
                            <i class="bi bi-chevron-down text-[10px] transition-transform duration-200"
                                :class="[open ? 'rotate-180' : '', sidebarOpen ? '' : 'opacity-0 invisible']"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition class="mt-2 space-y-1 ml-4 border-l border-white/5 pl-4"
                            :class="sidebarOpen ? '' : 'hidden'">
                            @foreach ($menu['children'] as $child)
                                <a href="{{ route($child['route']) }}"
                                    class="block py-2.5 px-4 text-xs font-bold uppercase tracking-widest transition-colors {{ $currentRoute === $child['route'] ? 'text-primary' : 'text-slate-500 hover:text-white' }}">
                                    {{ $child['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route($menu['route']) }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 group {{ $currentRoute === $menu['route'] ? 'bg-primary text-white shadow-lg shadow-primary/25' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <i class="bi {{ $menu['icon'] }} text-lg flex-shrink-0"></i>
                        <span class="transition-opacity duration-300"
                            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 invisible'">{{ $menu['title'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-white/5 flex-shrink-0">
            <div class="bg-slate-800/50 rounded-2xl p-4 flex items-center gap-3"
                :class="sidebarOpen ? '' : 'justify-center p-2'">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('admin')->user()->name ?? 'Admin') }}&background=20a7db&color=fff"
                    class="w-10 h-10 rounded-xl" alt="Admin">
                <div class="flex flex-col min-w-0 transition-opacity duration-300"
                    :class="sidebarOpen ? 'opacity-100' : 'opacity-0 invisible'">
                    <span
                        class="text-xs font-black uppercase tracking-widest truncate">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</span>
                    <span class="text-[10px] text-slate-500 font-bold truncate">Administrator</span>
                </div>
            </div>
        </div>
    </aside>

    {{-- Mobile Sidebar (Responsive) --}}
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed top-0 left-0 z-50 h-screen w-72 bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:hidden sidebar-scroll overflow-y-auto border-r border-white/5">
        <!-- (Same content as Desktop Sidebar, but always expanded) -->
        <div class="flex items-center gap-4 px-6 py-8 h-24 border-b border-white/5">
            <div
                class="bg-primary text-white w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-primary/20 flex-shrink-0">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="flex flex-col">
                <h1 class="m-0 text-xl font-black tracking-tighter leading-none uppercase italic">ONEMALL</h1>
                <span class="text-[8px] text-primary-light font-black tracking-[0.2em] uppercase mt-1">Admin
                    Central</span>
            </div>
            <button @click="mobileSidebarOpen = false" class="ml-auto text-slate-400 hover:text-white">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <nav class="flex-grow px-4 py-8 space-y-6">
            {{-- Duplicate Menu Logic for Mobile --}}
            @foreach ($menus as $menu)
                @if (isset($menu['children']))
                    @php
                        $childRoutes = array_column($menu['children'], 'route');
                        $isActive = in_array($currentRoute, $childRoutes);
                    @endphp
                    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                            class="flex items-center justify-between w-full px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ $isActive ? 'bg-white/5 text-primary' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <div class="flex items-center gap-3"><i
                                    class="bi {{ $menu['icon'] }} text-lg"></i><span>{{ $menu['title'] }}</span></div>
                            <i class="bi bi-chevron-down text-[10px] transition-transform"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition class="mt-2 space-y-1 ml-4 border-l border-white/5 pl-4">
                            @foreach ($menu['children'] as $child)
                                <a href="{{ route($child['route']) }}"
                                    class="block py-2.5 px-4 text-xs font-bold uppercase tracking-widest transition-colors {{ $currentRoute === $child['route'] ? 'text-primary' : 'text-slate-500 hover:text-white' }}">{{ $child['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route($menu['route']) }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 {{ $currentRoute === $menu['route'] ? 'bg-primary text-white shadow-lg' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <i class="bi {{ $menu['icon'] }} text-lg"></i><span>{{ $menu['title'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
    </aside>

    {{-- Main Content Wrapper --}}
    <div :class="sidebarOpen ? 'lg:ml-72' : 'lg:ml-20'"
        class="min-h-screen transition-all duration-300 ease-in-out flex flex-col">

        {{-- Topbar --}}
        <header
            class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200 h-24 flex items-center px-6 lg:px-10 justify-between">
            <div class="flex items-center gap-6">
                {{-- Desktop Sidebar Toggle --}}
                <button @click="sidebarOpen = !sidebarOpen"
                    class="hidden lg:flex w-10 h-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                    <i class="bi" :class="sidebarOpen ? 'bi-text-indent-left' : 'bi-text-indent-right'"></i>
                </button>
                {{-- Mobile Sidebar Toggle --}}
                <button @click="mobileSidebarOpen = true"
                    class="flex lg:hidden w-10 h-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 hover:bg-primary hover:text-white transition-all shadow-sm">
                    <i class="bi bi-list"></i>
                </button>
                <div class="hidden md:flex flex-col">
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 leading-none">
                        @yield('title', 'Admin Dashboard')</h2>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1.5">Onemall
                        Management Ecosystem</span>
                </div>
            </div>

            <div class="flex items-center gap-3 lg:gap-6">
                {{-- Global Search --}}
                <div class="hidden xl:flex relative">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Quick Search..."
                        class="bg-slate-50 border-none rounded-xl pl-12 pr-6 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-primary/20 w-64 transition-all focus:w-80 outline-none placeholder:text-slate-400">
                </div>

                {{-- Notifications --}}
                <button
                    class="relative w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-500 hover:text-primary hover:bg-primary/5 transition-all group">
                    <i class="bi bi-bell text-xl"></i>
                    <span
                        class="absolute top-3 right-3 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                </button>

                {{-- Profile Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu" @click.away="userMenu = false"
                        class="flex items-center gap-3 p-1.5 pr-4 rounded-2xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-200">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::guard('admin')->user()->name ?? 'Admin') }}&background=20a7db&color=fff"
                            class="w-10 h-10 rounded-xl" alt="User">
                        <div class="hidden lg:flex flex-col text-left">
                            <span
                                class="text-xs font-black uppercase tracking-widest text-slate-900">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</span>
                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Master
                                Admin</span>
                        </div>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400 transition-transform duration-300"
                            :class="userMenu ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="userMenu" x-cloak x-transition
                        class="absolute right-0 mt-4 w-56 bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-slate-100 py-2 z-50">
                        <a href="{{ route('admin.profile') }}"
                            class="flex items-center gap-3 px-5 py-3 text-sm font-bold text-slate-600 hover:text-primary hover:bg-slate-50 transition-all">
                            <i class="bi bi-person text-lg"></i> Profile Settings
                        </a>
                        <div class="h-px bg-slate-100 mx-5 my-2"></div>
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-5 py-3 text-sm font-bold text-red-500 hover:bg-red-50 transition-all">
                                <i class="bi bi-box-arrow-right text-lg"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-grow p-6 lg:p-10 max-w-[1600px] mx-auto w-full">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="p-10 border-t border-slate-200 bg-white">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-4">
                    <div
                        class="bg-slate-100 text-slate-400 w-10 h-10 rounded-xl flex items-center justify-center text-xl">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">
                        © 2026 ONEMALL ENGINE. v3.1.0-STABLE
                    </p>
                </div>
                <div class="flex gap-8 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <a href="#" class="hover:text-primary transition-colors">Documentation</a>
                    <a href="#" class="hover:text-primary transition-colors">Support</a>
                    <a href="#" class="hover:text-primary transition-colors">Security</a>
                </div>
            </div>
        </footer>
    </div>

    {{-- Quill JS --}}
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorElement = document.getElementById('editor');
            if (editorElement && !editorElement.classList.contains('quill-initialized')) {
                editorElement.classList.add('quill-initialized');
                const quill = new Quill('#editor', { theme: 'snow' });
                quill.root.innerHTML = `{!! old('content', $article->content ?? '') !!}`;
                const form = editorElement.closest('form');
                form.addEventListener('submit', function () {
                    document.getElementById('content').value = quill.root.innerHTML;
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>