<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * A private link that hasn't been filled in yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(40),
            'sent_to' => fake()->name(),
            'project_slug' => null,
        ];
    }

    /**
     * Filled in by the client, waiting for approval.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $attributes['sent_to'],
            'role' => fake()->jobTitle().', '.fake()->company(),
            'quote' => fake()->paragraph(),
            'submitted_at' => now(),
        ]);
    }

    /**
     * Approved and showing on the site.
     */
    public function approved(): static
    {
        return $this->submitted()->state(fn (array $attributes) => ['approved_at' => now()]);
    }
}
