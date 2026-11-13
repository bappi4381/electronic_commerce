<?php

namespace Database\Seeders;

use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (AdminPermissions::all() as $permissionName) {
            Permission::findOrCreate($permissionName, AdminPermissions::GUARD);
        }

        // Super Admin gets every ability through Gate::before (assigned in AdminSeeder)
        Role::findOrCreate(AdminPermissions::SUPER_ADMIN_ROLE, AdminPermissions::GUARD);

        // Manager: full catalogue and order handling, read-only payments
        Role::findOrCreate('Manager', AdminPermissions::GUARD)->syncPermissions(array_merge(
            AdminPermissions::forModule('products'),
            AdminPermissions::forModule('orders'),
            ['payments.view'],
        ));

        // Support: answer customers and look up their orders
        Role::findOrCreate('Support', AdminPermissions::GUARD)->syncPermissions([
            'orders.view',
            'users.view',
            'messages.view',
            'messages.create',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
