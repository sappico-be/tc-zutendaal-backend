<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin TC Zutendaal',
            'email' => 'admin@tczutendaal.be',
            'password' => Hash::make('TempPassword123!'),
            'email_verified_at' => now(),
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@tczutendaal.be',
            'password' => Hash::make('TestUser123!'),
            'email_verified_at' => now(),
        ]);
    }
}
