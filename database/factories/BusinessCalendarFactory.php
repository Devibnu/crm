<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCalendar>
 */
class BusinessCalendarFactory extends Factory
{
    protected $model = BusinessCalendar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Support Calendar '.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->optional()->sentence(12),
            'timezone' => fake()->randomElement(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC']),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
