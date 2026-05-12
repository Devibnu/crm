<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppBroadcastRecipient>
 */
class WhatsAppBroadcastRecipientFactory extends Factory
{
    protected $model = WhatsAppBroadcastRecipient::class;

    public function definition(): array
    {
        $recipientType = fake()->randomElement(['customer', 'lead']);
        $status = fake()->randomElement(['queued', 'sent', 'delivered', 'read', 'replied', 'failed']);
        $sentAt = in_array($status, ['sent', 'delivered', 'read', 'replied', 'failed'], true) ? fake()->dateTimeBetween('-30 days', 'now') : null;
        $deliveredAt = in_array($status, ['delivered', 'read', 'replied'], true) && $sentAt ? fake()->dateTimeBetween($sentAt, 'now') : null;
        $readAt = in_array($status, ['read', 'replied'], true) && $deliveredAt ? fake()->dateTimeBetween($deliveredAt, 'now') : null;
        $repliedAt = $status === 'replied' && $readAt ? fake()->dateTimeBetween($readAt, 'now') : null;

        return [
            'whatsapp_broadcast_id' => WhatsAppBroadcast::factory(),
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientType === 'customer'
                ? Customer::query()->inRandomOrder()->value('id')
                : Lead::query()->inRandomOrder()->value('id'),
            'recipient_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'status' => $status,
            'sent_at' => $sentAt,
            'delivered_at' => $deliveredAt,
            'read_at' => $readAt,
            'replied_at' => $repliedAt,
            'failed_reason' => $status === 'failed' ? fake()->randomElement(['Invalid number', 'Blocked recipient', 'Timeout']) : null,
        ];
    }
}
