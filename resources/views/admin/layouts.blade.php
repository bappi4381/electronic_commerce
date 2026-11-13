@php
    $admin = Auth::guard('admin')->user();

    // Pages rebuilt on the new design system declare @section('ui-v2', '1'): they get dark mode and toast flashes.
    $uiV2 = $__env->hasSection('ui-v2');

    $navigation = collect(config('admin_navigation'))
        ->map(function (array $group) use ($admin) {
            $group['items'] = collect($group['items'])
                ->filter(fn ($item) => !isset($item['can']) || $admin?->can($item['can']))
                ->filter(fn ($item) => Route::has($item['route']))
                ->map(fn ($item) => array_merge($item, [
                    'url' => route($item['route']),
                    'active' => request()->routeIs(...($item['active'] ?? [$item['route']])),
                ]))
                ->values()
                ->all();

            return $group;
        })
        ->filter(fn ($group) => $group['items'])
        ->values()
        ->all();

    $paletteShortcuts = collect([
        ['title' => 'Add product', 'icon' => 'bi-plus-lg', 'route' => 'admin.products.create', 'can' => 'products.create'],
        ['title' => 'Create order', 'icon' => 'bi-plus-lg', 'route' => 'orders.create', 'can' => 'orders.create'],
        ['title' => 'Write article', 'icon' => 'bi-plus-lg', 'route' => 'articles.create', 'can' => 'articles.create'],
        ['title' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'admin.notifications.index'],
        ['title' => 'Your profile', 'icon' => 'bi-person', 'route' => 'admin.profile'],
    ])->filter(fn ($item) => !isset($item['can']) || $admin?->can($item['can']))
      ->map(fn ($item) => ['title' => $item['title'], 'icon' => $item['icon'], 'url' => route($item['route']), 'group' => 'Shortcut']);

    $palettePages = collect($navigation)
        ->flatMap(fn ($group) => collect($group['items'])->map(fn ($item) => [
            'title' => $item['title'], 'icon' => $item['icon'], 'url' => $item['url'], 'group' => $group['label'] ?? 'Overview',
        ]))
        ->concat($paletteShortcuts)
        ->values()
        ->all();

    $paletteSearches = collect([
        ['label' => 'orders', 'route' => 'orders.index', 'param' => 'q', 'can' => 'orders.view'],
        ['label' => 'products', 'route' => 'admin.products.index', 'param' => 'search', 'can' => 'products.view'],
        ['label' => 'customers', 'route' => 'users.index', 'param' => 'search', 'can' => 'users.view'],
        ['label' => 'payments', 'route' => 'payments.index', 'param' => 'search', 'can' => 'payments.view'],
        ['label' => 'reviews', 'route' => 'admin.reviews.index', 'param' => 'search', 'can' => 'reviews.view'],
    ])->filter(fn ($s) => $admin?->can($s['can']))
      ->map(fn ($s) => ['label' => $s['label'], 'url' => route($s['route']), 'param' => $s['param']])
      ->values()
      ->all();

    $paletteLookups = $admin?->can('orders.create') && $admin->can('users.view')
        ? [['label' => 'Customers', 'icon' => 'bi-person', 'url' => route('admin.lookup.customers'), 'link' => route('users.show', '__ID__')]]
        : [];
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full" data-dark-ready="{{ $uiV2 ? '1' : '0' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · {{ \App\Models\Setting::get('site_name', 'ONEMALL') }} Admin</title>

    <script>
        (function () {
            var root = document.documentElement;
            try {
                var theme = localStorage.getItem('admin-theme');
                var prefersDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (root.dataset.darkReady === '1' && prefersDark) root.classList.add('dark');
                if (localStorage.getItem('admin-sidebar') === 'collapsed') root.classList.add('sidebar-collapsed');
            } catch (e) { /* storage unavailable */ }
        })();
        window.AdminConfig = {
            notificationsFeed: @js(route('admin.notifications.feed')),
            notificationsReadAll: @js(route('admin.notifications.readAll')),
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
    @stack('head')
</head>

<body class="h-full bg-gray-50 font-inter text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100" x-data>
    <a href="#main" class="sr-only z-[80] rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    <x-admin.sidebar :navigation="$navigation" />

    <div class="flex min-h-full flex-col transition-[padding] duration-200 lg:pl-64 sidebar-collapsed:lg:pl-[4.5rem]">
        <x-admin.topbar :admin="$admin" />

        <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1440px] flex-1 px-4 py-6 focus:outline-none sm:px-6 lg:px-8 lg:py-8">
            @yield('content')
        </main>

        <footer class="border-t border-gray-200 px-4 py-4 text-xs text-gray-500 sm:px-6 lg:px-8 dark:border-gray-800 dark:text-gray-400">
            © {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'ONEMALL') }} · Admin
        </footer>
    </div>

    <x-admin.command-palette :pages="$palettePages" :searches="$paletteSearches" :lookups="$paletteLookups" />
    <x-admin.confirm-dialog />
    <x-admin.toast :flash="$uiV2" />

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    @php($editorContent = (string) old('content', $article->content ?? ''))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorElement = document.getElementById('editor');
            if (editorElement && !editorElement.classList.contains('quill-initialized')) {
                editorElement.classList.add('quill-initialized');
                const quill = new Quill('#editor', { theme: 'snow' });
                const initialContent = @json($editorContent !== '' ? clean($editorContent) : '');
                quill.setContents(quill.clipboard.convert(initialContent), 'silent');
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
