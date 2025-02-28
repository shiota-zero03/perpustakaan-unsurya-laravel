<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'userId' => \Str::uuid(),
            'password' => \Hash::make('admin@123'),
            'role' => 'SuperAdmin',
            'status' => 'Active',
            'email_verified_at' => now()
        ]);
    }
}
