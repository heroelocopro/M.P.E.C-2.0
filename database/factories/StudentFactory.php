<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'document_type' => DocumentType::IdentityCard,
            'document_number' => fake()->unique()->numerify('##########'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'second_last_name' => fake()->optional()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'gender' => fake()->randomElement(['femenino', 'masculino', null]),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'status' => StudentStatus::Active,
            'graduated_at' => null,
        ];
    }
}
