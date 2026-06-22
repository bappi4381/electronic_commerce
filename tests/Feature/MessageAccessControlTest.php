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

test('unauthenticated users cannot access administrative message routes', function () {
    $this->get(route('admin.messages.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin without manage-messages permission cannot access message index', function () {
    // Create an admin without manage-messages permission
    $admin = Admin::create([
        'name' => 'Restricted Admin',
        'email' => 'restricted@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $this->actingAs($admin, 'admin')
        ->get(route('admin.messages.index'))
        ->assertStatus(403);
});

test('admin with manage-messages permission can access message index', function () {
    $admin = Admin::create([
        'name' => 'Message Handler Admin',
        'email' => 'handler@example.com',
        'password' => bcrypt('password'),
    ]);
    
    // Give the permission directly
    $admin->givePermissionTo('manage-messages');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.messages.index'))
        ->assertStatus(200)
        ->assertSee('Conversations');
});

test('super admin can access message index due to gate bypass', function () {
    $superAdmin = Admin::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.messages.index'))
        ->assertStatus(200);
});

test('admin with Manager role does not have manage-messages by default and gets 403', function () {
    $admin = Admin::create([
        'name' => 'Manager Admin',
        'email' => 'manager@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $managerRole = Role::where('name', 'Manager')->where('guard_name', 'admin')->first();
    $admin->assignRole($managerRole);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.messages.index'))
        ->assertStatus(403);
});

test('admin with Manager role gets access if manage-messages permission is synced to Manager role', function () {
    $admin = Admin::create([
        'name' => 'Manager Admin',
        'email' => 'manager@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $managerRole = Role::where('name', 'Manager')->where('guard_name', 'admin')->first();
    $admin->assignRole($managerRole);

    // Sync manage-messages permission to Manager role
    $managerRole->givePermissionTo('manage-messages');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.messages.index'))
        ->assertStatus(200);
});
