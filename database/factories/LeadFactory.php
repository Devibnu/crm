<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'code' => sprintf('LED-%06d', fake()->unique()->numberBetween(1, 999999)),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'source' => 'manual',
            'status' => 'new',
            'assigned_user_id' => null,
            'captured_by' => null,
            'qualification_notes' => null,
            'last_contacted_at' => null,
            'qualified_at' => null,
            'disqualified_at' => null,
            'metadata' => null,
        ];
    }
}