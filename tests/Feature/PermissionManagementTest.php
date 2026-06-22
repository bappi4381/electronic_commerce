<?php

use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\AdminSeeder::class);
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ─── Index ────────────────────────────────────────────────────────────────────

test('unauthenticated users cannot access permissions index', function () {
    $this->get(route('permissions.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin without manage-roles permission gets 403 on permissions index', function () {
    $admin = Admin::create([
        'name'     => 'Limited Admin',
        'email'    => 'limited@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('permissions.index'))
        ->assertStatus(403);
});

test('super admin can view the permissions list', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->get(route('permissions.index'))
        ->assertStatus(200)
        ->assertSee('Permission Registry');
});

// ─── Create ───────────────────────────────────────────────────────────────────

test('super admin can access the create permission form', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->get(route('permissions.create'))
        ->assertStatus(200)
        ->assertSee('Create New Permission');
});

test('super admin can create a new custom permission', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->post(route('permissions.store'), [
            'name' => 'view-analytics',
        ])
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('permissions', [
        'name'       => 'view-analytics',
        'guard_name' => 'admin',
    ]);
});

test('permission name must be lowercase kebab-case only', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->post(route('permissions.store'), [
            'name' => 'View Analytics', // invalid — has spaces and uppercase
        ])
        ->assertSessionHasErrors(['name']);
});

test('duplicate permission names are rejected', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->post(route('permissions.store'), [
            'name' => 'manage-products', // already seeded
        ])
        ->assertSessionHasErrors(['name']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

test('super admin can rename a custom permission', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $permission = Permission::create(['name' => 'old-permission', 'guard_name' => 'admin']);

    $this->actingAs($superAdmin, 'admin')
        ->put(route('permissions.update', $permission), [
            'name' => 'new-permission',
        ])
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('permissions', ['name' => 'new-permission', 'guard_name' => 'admin']);
    $this->assertDatabaseMissing('permissions', ['name' => 'old-permission']);
});

test('core permissions cannot be renamed', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $corePermission = Permission::where('name', 'manage-products')->first();

    $this->actingAs($superAdmin, 'admin')
        ->put(route('permissions.update', $corePermission), [
            'name' => 'something-else',
        ])
        ->assertSessionHasErrors();
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

test('super admin can delete a custom permission', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $permission = Permission::create(['name' => 'deletable-perm', 'guard_name' => 'admin']);

    $this->actingAs($superAdmin, 'admin')
        ->delete(route('permissions.destroy', $permission))
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('permissions', ['name' => 'deletable-perm']);
});

test('core permissions cannot be deleted', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $corePermission = Permission::where('name', 'manage-orders')->first();

    $this->actingAs($superAdmin, 'admin')
        ->delete(route('permissions.destroy', $corePermission))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('permissions', ['name' => 'manage-orders']);
});
