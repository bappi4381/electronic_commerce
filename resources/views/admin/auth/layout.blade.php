@php($siteName = \App\Models\Setting::get('site_name', 'ONEMALL'))
<!DOCTYPE html>
<html lang="en" class="h-full" data-dark-ready="1">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Sign in') · {{ $siteName }} Admin</title>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('admin-theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) { /* storage unavailable */ }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>

<body class="h-full bg-white font-inter text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100" x-data>
    <div class="flex min-h-full">
        <main class="flex flex-1 flex-col px-4 py-8 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="flex items-center justify-between">
                <a href="{{ url('/en') }}" class="flex items-center gap-3 rounded-lg focus-ring">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-white dark:bg-brand-500 dark:text-gray-950" aria-hidden="true">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </span>
                    <span class="text-base font-semibold tracking-tight">{{ $siteName }}</span>
                </a>
                <button type="button" @click="$store.theme.toggle()"
                        class="inline-flex size-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-ring dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100">
                    <span class="sr-only" x-text="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'">Toggle dark mode</span>
                    <i class="bi" :class="$store.theme.dark ? 'bi-sun' : 'bi-moon-stars'" aria-hidden="true"></i>
                </button>
            </div>

            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12 lg:w-96">
                @yield('card')
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                © {{ date('Y') }} {{ $siteName }} ·
                <a href="{{ url('/en') }}" class="rounded font-medium text-gray-700 hover:underline focus-ring dark:text-gray-300">Back to store</a>
            </p>
        </main>

        <aside class="relative hidden flex-1 overflow-hidden bg-brand-950 lg:block" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,var(--color-brand-600),transparent_55%),radial-gradient(circle_at_80%_80%,var(--color-brand-800),transparent_50%)] opacity-80"></div>
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
            <div class="relative flex h-full flex-col justify-end p-16 text-white">
                <div class="mb-10 grid max-w-md grid-cols-2 gap-4">
                    @foreach([['bi-bag-check', 'Orders & fulfilment'], ['bi-box-seam', 'Catalog & inventory'], ['bi-graph-up-arrow', 'Sales reports'], ['bi-shield-lock', 'Role-based access']] as [$icon, $label])
                        <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur">
                            <i class="bi {{ $icon }} text-lg text-brand-300"></i>
                            <p class="mt-2 text-sm font-medium text-white/90">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="max-w-md text-2xl font-semibold leading-snug tracking-tight">Everything you need to run {{ $siteName }}, in one place.</p>
                <p class="mt-3 max-w-md text-sm text-white/70">Manage products, orders, customers and content from a single, secure dashboard.</p>
            </div>
        </aside>
    </div>

    <x-admin.toast :flash="false" />
    @stack('scripts')
</body>
</html>
