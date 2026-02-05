<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'status' => TicketStatus::Open,
            'category' => fake()->randomElement(TicketCategory::cases()),
            'priority' => fake()->randomElement(TicketPriority::cases()),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Open,
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::Low,
        ]);
    }

    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::Medium,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::High,
        ]);
    }
}
