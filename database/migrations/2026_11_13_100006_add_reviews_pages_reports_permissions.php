<?php

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the reviews/pages/reports permissions and grants them to roles that
 * already manage the closest existing area.
 */
return new class extends Migration
{
    private const GRANTS = [
        'reviews' => 'products.edit',
        'pages'   => 'settings.edit',
        'reports' => 'payments.view',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminPermissions::all() as $name) {
            Permission::findOrCreate($name, AdminPermissions::GUARD);
        }

        foreach (self::GRANTS as $module => $existing) {
            Role::where('guard_name', AdminPermissions::GUARD)->get()
                ->filter(fn (Role $role) => $role->hasPermissionTo($existing))
                ->each(fn (Role $role) => $role->givePermissionTo(AdminPermissions::forModule($module)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', AdminPermissions::GUARD)
            ->where(fn ($q) => $q->where('name', 'like', 'reviews.%')->orWhere('name', 'like', 'pages.%')->orWhere('name', 'like', 'reports.%'))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
