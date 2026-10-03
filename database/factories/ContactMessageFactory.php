<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'topic' => fake()->randomElement(array_keys(config('portfolio.contact_topics'))),
            'message' => fake()->paragraph(),
            'emailed_at' => now(),
            'read_at' => null,
        ];
    }

    /**
     * A message the admin has already opened.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }
}
