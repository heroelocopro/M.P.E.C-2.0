<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory()->withRole(UserRole::Administrator),
            'kind' => 'institucional',
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'published_at' => now(),
        ];
    }
}
