<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('auth.super_admin.name') ?: 'Super Admin';
        $email = config('auth.super_admin.email');
        $password = config('auth.super_admin.password');

        if (blank($email) || blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('Set ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD before seeding the super admin in production.');
            }

            $email = $email ?: 'admin@example.test';
            $password = Str::password(20);
            $this->command?->warn("Local super admin {$email} seeded with generated password: {$password}");
        }

        $admin = Admin::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $admin->assignRole(Role::findOrCreate('Super Admin', 'admin'));
    }
}
