<?php

namespace Database\Factories;

use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WhatsAppBroadcastReply>
 */
class WhatsAppBroadcastReplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'whatsapp_broadcast_id' => WhatsAppBroadcast::query()->inRandomOrder()->value('id') ?? WhatsAppBroadcast::factory(),
            'whatsapp_broadcast_recipient_id' => WhatsAppBroadcastRecipient::query()->inRandomOrder()->value('id'),
            'sender_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'message' => fake()->sentence(10),
            'status' => fake()->randomElement(['unread', 'read', 'resolved', 'archived']),
            'received_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
