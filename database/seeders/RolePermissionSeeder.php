<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Admin;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-products',
            'manage-orders',
            'manage-users',
            'manage-roles',
            'manage-messages',
            'manage-payments',
            'manage-banners',
            'manage-articles',
            'manage-marketing',
            'manage-settings',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'admin');
        }

        // Create Super Admin role under 'admin' guard
        $superAdminRole = Role::findOrCreate('Super Admin', 'admin');

        // Optional: Create Manager role with restricted permissions
        $managerRole = Role::findOrCreate('Manager', 'admin');
        $managerRole->syncPermissions([
            'manage-products',
            'manage-orders',
        ]);

        // Assign Super Admin role to default administrator account
        $admin = Admin::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole($superAdminRole);
        }
    }
}
