<?php

use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed initial roles/permissions using the seeder
    $this->seed(\Database\Seeders\AdminSeeder::class);
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('unauthenticated users cannot access role management', function () {
    $this->get(route('roles.index'))
        ->assertRedirect(route('admin.login'));
});

test('unauthorized admins cannot access role management', function () {
    $admin = Admin::create([
        'name' => 'Regular Admin',
        'email' => 'regular@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $this->actingAs($admin, 'admin')
        ->get(route('roles.index'))
        ->assertStatus(403);
});

test('super admin can access role management list', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->get(route('roles.index'));

    $response->assertStatus(200)
        ->assertSee('ROLES');
});

test('super admin can create a new role and sync permissions', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();
    $permission = Permission::where('name', 'manage-products')->first();

    $response = $this->actingAs($superAdmin, 'admin')
        ->post(route('roles.store'), [
            'name' => 'Editor',
            'permissions' => [$permission->id],
        ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'name' => 'Editor',
        'guard_name' => 'admin',
    ]);

    $role = Role::findByName('Editor', 'admin');
    expect($role->hasPermissionTo('manage-products'))->toBeTrue();
});
