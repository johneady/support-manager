<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::factory()->admin()->create([
            'name' => 'Tony Testing',
            'email' => 'tony@testing.com',
            'password' => 'testing',
        ]);

        $this->call([
            TicketCategorySeeder::class,
            FaqSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
