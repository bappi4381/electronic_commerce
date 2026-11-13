<?php

/*
|--------------------------------------------------------------------------
| Admin Permission Catalogue
|--------------------------------------------------------------------------
|
| Every permission here is a "core" permission named "{module}.{action}" on
| the admin guard. They are seeded, cannot be renamed or deleted from the UI,
| and are what the admin routes check. "legacy" maps the old manage-* names to
| their module so existing roles can be migrated.
|
*/

return [
    'modules' => [
        'products'  => ['label' => 'Inventory, Products & Brands', 'actions' => ['view', 'create', 'edit', 'delete']],
        'reviews'   => ['label' => 'Product Reviews',      'actions' => ['view', 'edit', 'delete']],
        'orders'    => ['label' => 'Orders & Fulfilment',  'actions' => ['view', 'create', 'edit', 'delete']],
        'payments'  => ['label' => 'Payments & Refunds',   'actions' => ['view', 'edit']],
        'users'     => ['label' => 'Customers',            'actions' => ['view', 'create', 'edit', 'delete']],
        'marketing' => ['label' => 'Marketing, Coupons & Subscribers', 'actions' => ['view', 'create', 'edit', 'delete']],
        'banners'   => ['label' => 'Banners',              'actions' => ['view', 'create', 'edit', 'delete']],
        'articles'  => ['label' => 'Tech Blog',            'actions' => ['view', 'create', 'edit', 'delete']],
        'pages'     => ['label' => 'CMS Pages',            'actions' => ['view', 'create', 'edit', 'delete']],
        'reports'   => ['label' => 'Reports',              'actions' => ['view']],
        'messages'  => ['label' => 'Customer Messages',    'actions' => ['view', 'create']],
        'settings'  => ['label' => 'Global Settings',      'actions' => ['view', 'edit']],
        'roles'     => ['label' => 'Access Control',       'actions' => ['view', 'create', 'edit', 'delete']],
        'activity'  => ['label' => 'Activity Log',         'actions' => ['view']],
    ],

    'action_labels' => [
        'view'   => 'View',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],

    'legacy' => [
        'manage-products'  => ['products'],
        'manage-orders'    => ['orders'],
        'manage-payments'  => ['payments'],
        'manage-users'     => ['users'],
        'manage-marketing' => ['marketing'],
        'manage-banners'   => ['banners'],
        'manage-articles'  => ['articles'],
        'manage-messages'  => ['messages'],
        'manage-settings'  => ['settings'],
        'manage-roles'     => ['roles', 'activity'],
    ],
];
