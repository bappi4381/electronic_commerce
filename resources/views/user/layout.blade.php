<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'User Dashboard') - ONEMALL</title>

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Custom Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #20a7db;
            --primary-dark: #1c96c5;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-50: #f8fafc;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--slate-50); }
        .sidebar { 
            width: 280px; 
            height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            background: var(--slate-900); 
            z-index: 50;
            transition: all 0.3s ease;
        }
        .main-content { 
            margin-left: 280px; 
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        @media (max-width: 1024px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
        }
        .nav-link-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .nav-link-item:hover, .nav-link-item.active {
            color: #fff;
            background: rgba(255,255,255,0.03);
        }
        .nav-link-item.active {
            background: linear-gradient(90deg, rgba(32, 167, 219, 0.1) 0%, rgba(32, 167, 219, 0) 100%);
            border-right: 3px solid var(--primary);
        }
        .nav-link-item i {
            color: var(--primary);
            margin-right: 0.75rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .nav-link-item:hover i {
            transform: scale(1.2);
            filter: drop-shadow(0 0 8px rgba(32, 167, 219, 0.5));
        }
    </style>
</head>
<body class="antialiased text-slate-700">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar shadow-2xl">
        <div class="p-8 border-b border-white/5">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group no-underline">
                <div class="bg-primary text-white w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-primary/20">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div class="flex flex-col">
                    <h1 class="m-0 text-lg font-black tracking-tighter leading-none uppercase italic text-white">ONEMALL</h1>
                    <span class="text-[7px] text-primary font-black tracking-[0.3em] uppercase mt-1 text-left">Dashboard</span>
                </div>
            </a>
        </div>

        <nav class="mt-6">
            @php
                $currentRoute = Route::currentRouteName();
                $menus = [
                    ['title' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'user.dashboard'],
                    ['title' => 'My Profile', 'icon' => 'bi-person-fill', 'route' => 'user.profile'],
                    ['title' => 'My Orders', 'icon' => 'bi-bag-fill', 'route' => 'user.orders.index'],
                    ['title' => 'Track Order', 'icon' => 'bi-geo-alt-fill', 'route' => 'user.orders.track'],
                    ['title' => 'Messages', 'icon' => 'bi-chat-dots-fill', 'route' => 'user.messages.index'],
                ];
            @endphp

            <div class="px-4 mb-4">
                <p class="text-[9px] font-black uppercase text-slate-500 tracking-[0.2em] px-4 mb-4">Menu</p>
                <div class="space-y-1">
                    @foreach ($menus as $menu)
                        <a href="{{ route($menu['route']) }}" class="nav-link-item rounded-xl {{ $currentRoute === $menu['route'] ? 'active' : '' }}">
                            <i class="bi {{ $menu['icon'] }}"></i>
                            <span>{{ $menu['title'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="px-4 mt-auto absolute bottom-8 w-full">
                <form action="{{ route('auth.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link-item w-full rounded-xl text-rose-400 hover:text-rose-500 hover:bg-rose-500/5 border border-transparent hover:border-rose-500/10 transition-all">
                        <i class="bi bi-box-arrow-right text-rose-400"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content flex flex-col">
        <!-- Top Navbar -->
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button id="sidebarToggle" class="lg:hidden text-2xl text-slate-400 hover:text-slate-900 transition-colors">
                    <i class="bi bi-list"></i>
                </button>
                <div class="hidden md:flex items-center gap-2 text-[10px] font-black uppercase text-slate-400 tracking-widest">
                    <span>Account</span>
                    <i class="bi bi-chevron-right text-[8px]"></i>
                    <span class="text-slate-900">@yield('title')</span>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Notifications -->
                <button class="relative w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-primary hover:bg-primary/5 transition-all flex items-center justify-center">
                    <i class="bi bi-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white"></span>
                </button>

                <!-- User Profile -->
                @php
                    $user = Auth::user();
                    $avatarUrl = $user && $user->avatar 
                        ? asset('storage/' . $user->avatar)
                        : 'https://via.placeholder.com/32x32/f1f5f9/94a3b8?text=' . strtoupper(substr($user->name ?? 'U', 0, 2));
                @endphp
                <div class="flex items-center gap-3 pl-6 border-l border-slate-100">
                    <div class="text-right hidden sm:block">
                        <p class="text-[11px] font-black text-slate-900 leading-none mb-1">{{ Auth::user()->name }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Customer</p>
                    </div>
                    <img src="{{ $avatarUrl }}" class="w-10 h-10 rounded-xl object-cover border-2 border-white shadow-sm" alt="User">
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 sm:p-8 lg:p-12 pb-24 lg:pb-12">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-auto py-8 px-12 border-t border-slate-100 bg-white hidden lg:block">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    &copy; 2026 <span class="text-slate-900">ONEMALL</span>. All rights reserved.
                </p>
                <div class="flex gap-6">
                    <a href="#" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hover:text-primary transition-colors">Privacy Policy</a>
                    <a href="#" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hover:text-primary transition-colors">Terms of Service</a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Mobile Bottom Navigation Bar (visible on < lg) -->
    @php
        $mobileCurrentRoute = Route::currentRouteName();
    @endphp
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-100 shadow-2xl lg:hidden" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="grid grid-cols-5 h-16">

            <!-- Dashboard -->
            <a href="{{ route('user.dashboard') }}" class="flex flex-col items-center justify-center gap-1 transition-colors {{ $mobileCurrentRoute === 'user.dashboard' ? 'text-primary' : 'text-slate-400 hover:text-slate-700' }}">
                <i class="bi bi-grid-1x2-fill text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Home</span>
            </a>

            <!-- Orders -->
            <a href="{{ route('user.orders.index') }}" class="flex flex-col items-center justify-center gap-1 transition-colors {{ $mobileCurrentRoute === 'user.orders.index' || $mobileCurrentRoute === 'user.orders.details' ? 'text-primary' : 'text-slate-400 hover:text-slate-700' }}">
                <i class="bi bi-bag-fill text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Orders</span>
            </a>

            <!-- Track (centre accent) -->
            <a href="{{ route('user.orders.track') }}" class="flex flex-col items-center justify-center gap-1 -mt-4">
                <span class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-xl shadow-primary/30 transition-all {{ $mobileCurrentRoute === 'user.orders.track' ? 'bg-primary-dark scale-95' : 'bg-primary hover:bg-primary-dark' }}">
                    <i class="bi bi-geo-alt-fill text-white text-xl"></i>
                </span>
                <span class="text-[8px] font-black uppercase tracking-widest mt-0.5 {{ $mobileCurrentRoute === 'user.orders.track' ? 'text-primary' : 'text-slate-400' }}">Track</span>
            </a>

            <!-- Messages -->
            <a href="{{ route('user.messages.index') }}" class="flex flex-col items-center justify-center gap-1 transition-colors {{ $mobileCurrentRoute === 'user.messages.index' ? 'text-primary' : 'text-slate-400 hover:text-slate-700' }}">
                <i class="bi bi-chat-dots-fill text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Chat</span>
            </a>

            <!-- Profile -->
            <a href="{{ route('user.profile') }}" class="flex flex-col items-center justify-center gap-1 transition-colors {{ $mobileCurrentRoute === 'user.profile' ? 'text-primary' : 'text-slate-400 hover:text-slate-700' }}">
                <i class="bi bi-person-fill text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Profile</span>
            </a>

        </div>
    </nav>

    <script>
        // Sidebar Toggle for Mobile
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        
        if(toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Close sidebar on click outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
