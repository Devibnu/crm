<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use App\Models\BusinessCalendarHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCalendarHoliday>
 */
class BusinessCalendarHolidayFactory extends Factory
{
    protected $model = BusinessCalendarHoliday::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_calendar_id' => BusinessCalendar::factory(),
            'holiday_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'name' => fake()->randomElement(['National Holiday', 'Company Holiday', 'Public Leave']),
            'description' => fake()->optional()->sentence(10),
            'is_recurring' => false,
        ];
    }
}
