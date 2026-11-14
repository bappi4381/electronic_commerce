<?php

/*
|--------------------------------------------------------------------------
| Admin Navigation
|--------------------------------------------------------------------------
|
| Drives the sidebar and the command palette. "can" is the permission needed
| to see an item (omit for everyone); "active" lists route-name patterns that
| highlight it (defaults to the item's route). Empty groups are hidden.
|
*/

return [
    [
        'label' => null,
        'items' => [
            ['title' => 'Dashboard', 'icon' => 'bi-grid-1x2', 'route' => 'admin.dashboard'],
        ],
    ],
    [
        'label' => 'Catalog',
        'items' => [
            ['title' => 'Products', 'icon' => 'bi-box-seam', 'route' => 'admin.products.index', 'can' => 'products.view',
                'active' => ['admin.products.index', 'admin.products.create', 'admin.products.edit', 'admin.products.show']],
            ['title' => 'Categories', 'icon' => 'bi-diagram-3', 'route' => 'category_subcategory.index', 'can' => 'products.view'],
            ['title' => 'Attributes', 'icon' => 'bi-sliders', 'route' => 'admin.attributes.index', 'can' => 'products.view'],
            ['title' => 'Brands', 'icon' => 'bi-award', 'route' => 'admin.brands.index', 'can' => 'products.view'],
            ['title' => 'Inventory', 'icon' => 'bi-arrow-left-right', 'route' => 'admin.products.stock_movements.index', 'can' => 'products.view'],
            ['title' => 'Reviews', 'icon' => 'bi-star-half', 'route' => 'admin.reviews.index', 'can' => 'reviews.view'],
            ['title' => 'Bulk import', 'icon' => 'bi-cloud-upload', 'route' => 'admin.products.import', 'can' => 'products.create'],
        ],
    ],
    [
        'label' => 'Sales',
        'items' => [
            ['title' => 'Orders', 'icon' => 'bi-bag-check', 'route' => 'orders.index', 'can' => 'orders.view', 'active' => ['orders.*', 'admin.orders.*']],
            ['title' => 'Payments', 'icon' => 'bi-credit-card', 'route' => 'payments.index', 'can' => 'payments.view', 'active' => ['payments.*']],
            ['title' => 'Reports', 'icon' => 'bi-bar-chart-line', 'route' => 'admin.reports.index', 'can' => 'reports.view'],
        ],
    ],
    [
        'label' => 'Customers',
        'items' => [
            ['title' => 'Customers', 'icon' => 'bi-people', 'route' => 'users.index', 'can' => 'users.view', 'active' => ['users.*']],
            ['title' => 'Messages', 'icon' => 'bi-chat-dots', 'route' => 'admin.messages.index', 'can' => 'messages.view'],
            ['title' => 'Phone blacklist', 'icon' => 'bi-telephone-x', 'route' => 'admin.blacklist.index', 'can' => 'orders.view'],
        ],
    ],
    [
        'label' => 'Marketing',
        'items' => [
            ['title' => 'Coupons & deals', 'icon' => 'bi-ticket-perforated', 'route' => 'marketing.index', 'can' => 'marketing.view', 'active' => ['marketing.*', 'coupons.*']],
            ['title' => 'Subscribers', 'icon' => 'bi-envelope-paper', 'route' => 'admin.subscribers.index', 'can' => 'marketing.view'],
            ['title' => 'Banners', 'icon' => 'bi-images', 'route' => 'banners.index', 'can' => 'banners.view', 'active' => ['banners.*']],
        ],
    ],
    [
        'label' => 'Content',
        'items' => [
            ['title' => 'Tech blog', 'icon' => 'bi-journal-richtext', 'route' => 'articles.index', 'can' => 'articles.view', 'active' => ['articles.*']],
            ['title' => 'CMS pages', 'icon' => 'bi-file-earmark-text', 'route' => 'admin.pages.index', 'can' => 'pages.view', 'active' => ['admin.pages.*']],
        ],
    ],
    [
        'label' => 'Settings',
        'items' => [
            ['title' => 'Store settings', 'icon' => 'bi-gear', 'route' => 'admin.settings.index', 'can' => 'settings.view'],
            ['title' => 'Administrators', 'icon' => 'bi-person-badge', 'route' => 'admins.index', 'can' => 'roles.view', 'active' => ['admins.*']],
            ['title' => 'Roles & permissions', 'icon' => 'bi-shield-lock', 'route' => 'roles.index', 'can' => 'roles.view', 'active' => ['roles.*', 'permissions.*']],
            ['title' => 'Activity log', 'icon' => 'bi-clock-history', 'route' => 'admin.activity.index', 'can' => 'activity.view'],
        ],
    ],
];
