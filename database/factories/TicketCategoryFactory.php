<?php

namespace Database\Factories;

use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => str()->slug(fake()->unique()->word()),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->randomElement(['red', 'blue', 'green', 'amber', 'zinc', 'sky', 'emerald', 'rose']),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
