{{-- Standalone error page shell. Must not touch the database: it also renders 500s. --}}
@php
    $inAdmin = request()->is('admin', 'admin/*');
    $homeUrl = $inAdmin ? url('/admin/dashboard') : url('/');
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full" data-dark-ready="1">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <script>
        try {
            var theme = localStorage.getItem('admin-theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (e) { /* storage unavailable */ }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-gray-50 px-4 font-inter text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <main class="w-full max-w-md py-16 text-center">
        <span class="mx-auto mb-6 flex size-14 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600 dark:bg-brand-500/10 dark:text-brand-400" aria-hidden="true">
            <i class="bi @yield('icon', 'bi-exclamation-circle')"></i>
        </span>
        <p class="text-sm font-semibold text-brand-600 dark:text-brand-400">@yield('code')</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">@yield('heading')</h1>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">@yield('message')</p>
        <div class="mt-8 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="{{ $homeUrl }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-xs hover:bg-brand-700 focus-ring dark:bg-brand-500 dark:text-gray-950 dark:hover:bg-brand-400">
                <i class="bi {{ $inAdmin ? 'bi-speedometer2' : 'bi-house-door' }}" aria-hidden="true"></i> {{ $inAdmin ? 'Go to dashboard' : 'Back to homepage' }}
            </a>
            @hasSection('secondary')
                @yield('secondary')
            @else
                <button type="button" onclick="history.length > 1 ? history.back() : location.assign(@js($homeUrl))"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus-ring dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Go back
                </button>
            @endif
        </div>
    </main>
</body>
</html>
