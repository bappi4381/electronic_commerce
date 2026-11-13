<?php

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Replaces the coarse manage-* permissions with {module}.{action} permissions.
 * Roles that had manage-X receive every action of module X.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminPermissions::all() as $name) {
            Permission::findOrCreate($name, AdminPermissions::GUARD);
        }

        $legacy = config('admin_permissions.legacy');

        foreach (Role::where('guard_name', AdminPermissions::GUARD)->with('permissions')->get() as $role) {
            $grant = [];
            foreach ($role->permissions as $permission) {
                foreach ($legacy[$permission->name] ?? [] as $module) {
                    $grant = array_merge($grant, AdminPermissions::forModule($module));
                }
            }

            if ($grant) {
                $role->givePermissionTo(array_unique($grant));
            }
        }

        Permission::where('guard_name', AdminPermissions::GUARD)->whereIn('name', array_keys($legacy))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('admin_permissions.legacy') as $legacyName => $modules) {
            $permission = Permission::findOrCreate($legacyName, AdminPermissions::GUARD);

            Role::where('guard_name', AdminPermissions::GUARD)->get()
                ->filter(fn (Role $role) => $role->hasAnyPermission(AdminPermissions::forModule($modules[0])))
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        Permission::where('guard_name', AdminPermissions::GUARD)->whereIn('name', AdminPermissions::all())->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
