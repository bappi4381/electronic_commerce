<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create('bn_BD'); // Bengali (Bangladesh) locale for more realistic names
        
        for ($i = 0; $i < 50000000; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => 'password123', // will be hashed automatically by User model
                'phone' => $faker->numerify('+8801#########'), // Bangladeshi phone number format
                'address' => $faker->address,
                'city' => $faker->city,
                'state' => $faker->state,
                'postal_code' => $faker->postcode,
                'country' => 'Bangladesh',
                'is_email_verified' => $faker->boolean(80), // 80% chance verified
                'email_verified_at' => now(),
            ]);
        }
    }
}
