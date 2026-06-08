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
            'display_phone_number' => $provider === 'meta' ? fake()->optional(0.5)->phoneNumber() : null,
            'verified_name' => $provider === 'meta' ? fake()->optional(0.5)->company() : null,
            'webhook_secret' => fake()->optional(0.7)->sha1(),
            'business_account_id' => $provider === 'meta' ? fake()->numerify('###############') : null,
            'graph_api_version' => $provider === 'meta' ? 'v23.0' : null,
            'meta_template_name' => $provider === 'meta' ? 'crm_notification' : null,
            'meta_template_language' => $provider === 'meta' ? 'id' : null,
            'meta_connection_status' => null,
            'meta_connection_error' => null,
            'status' => fake()->randomElement(['active', 'inactive']),
            'is_default' => false,
            'notes' => fake()->optional(0.6)->sentence(10),
            'last_connected_at' => fake()->optional(0.5)->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
