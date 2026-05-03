<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewRoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id'  => Application::factory(),
            'type'            => fake()->randomElement(['technical', 'hr', 'system_design', 'take_home']),
            'date'            => fake()->dateTimeBetween('-2 months', '+2 weeks')->format('Y-m-d'),
            'interviewer_name'=> fake()->boolean(60) ? fake()->name() : null,
            'notes'           => fake()->boolean(70) ? fake()->sentences(3, true) : null,
            'self_rating'     => fake()->numberBetween(1, 5),
        ];
    }
}
