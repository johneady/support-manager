<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Tony Testing',
            'email' => 'tony@testing.com',
            'password' => 'testing',
            'is_admin' => true,
        ]);

        $this->call([
            UserSeeder::class,
            FaqSeeder::class,
            // TicketSeeder::class,
        ]);
    }
}
