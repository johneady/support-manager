<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TicketCategory::create([
            'name' => 'Technical Support',
            'slug' => 'technical-support',
            'description' => 'Technical problems, errors, bugs, or system issues',
            'color' => 'red',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        TicketCategory::create([
            'name' => 'Sales Support',
            'slug' => 'sales-support',
            'description' => 'Questions about pricing, plans, billing, or sales inquiries',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        TicketCategory::create([
            'name' => 'General Inquiry',
            'slug' => 'general-inquiry',
            'description' => 'General questions, feedback, or other inquiries',
            'color' => 'zinc',
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}
