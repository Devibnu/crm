<?php

namespace Database\Factories;

use App\Models\WhatsAppProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppProvider>
 */
class WhatsAppProviderFactory extends Factory
{
    protected $model = WhatsAppProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['fonnte', 'wablas', 'meta']);

        return [
            'name' => ucfirst($provider) . ' ' . fake()->company(),
            'provider' => $provider,
            'api_url' => match ($provider) {
                'fonnte' => 'https://api.fonnte.com',
                'wablas' => 'https://solo.wablas.com',
                default => 'https://graph.facebook.com',
            },
            'api_token' => fake()->sha256(),
            'device_id' => fake()->optional(0.8)->bothify('device-####'),
            'webhook_secret' => fake()->optional(0.7)->sha1(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'is_default' => false,
            'notes' => fake()->optional(0.6)->sentence(10),
            'last_connected_at' => fake()->optional(0.5)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
