<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin users
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->admin()->create([
            'name' => 'Support Manager',
            'email' => 'support@example.com',
        ]);

        // Create regular users
        User::factory(20)->create();

        // Create some unverified users
        User::factory(5)->unverified()->create();

        // Create some users with 2FA enabled
        User::factory(3)->withTwoFactor()->create();
    }
}
