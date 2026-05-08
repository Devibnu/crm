<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OmnichannelMessage>
 */
class OmnichannelMessageFactory extends Factory
{
    public function definition(): array
    {
        $channel = fake()->randomElement(['whatsapp', 'email', 'livechat', 'facebook', 'instagram', 'telegram']);
        $direction = fake()->randomElement(['inbound', 'outbound']);
        $status = fake()->randomElement(['unread', 'read', 'pending', 'resolved']);
        $receivedAt = fake()->dateTimeBetween('-90 days', 'now');

        return [
            'customer_id' => fake()->boolean(65) ? Customer::query()->inRandomOrder()->value('id') : null,
            'channel' => $channel,
            'direction' => $direction,
            'sender_name' => fake()->name(),
            'sender_contact' => $this->senderContact($channel),
            'subject' => fake()->boolean($channel === 'email' ? 95 : 55) ? fake()->sentence(4) : null,
            'message' => fake()->paragraph(),
            'status' => $status,
            'assigned_to' => fake()->optional(0.7)->name(),
            'received_at' => $receivedAt,
            'resolved_at' => $status === 'resolved' ? fake()->dateTimeBetween($receivedAt, 'now') : null,
        ];
    }

    protected function senderContact(string $channel): string
    {
        return match ($channel) {
            'email' => fake()->safeEmail(),
            'whatsapp', 'telegram' => fake()->phoneNumber(),
            'facebook' => 'facebook.com/'.fake()->userName(),
            'instagram' => '@'.fake()->userName(),
            default => fake()->userName(),
        };
    }
}
