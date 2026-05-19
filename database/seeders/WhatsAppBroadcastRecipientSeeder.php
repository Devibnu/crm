<?php

namespace Database\Seeders;

use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use Illuminate\Database\Seeder;

class WhatsAppBroadcastRecipientSeeder extends Seeder
{
    public function run(): void
    {
        $broadcastIds = WhatsAppBroadcast::query()->pluck('id');

        foreach ($broadcastIds as $broadcastId) {
            WhatsAppBroadcastRecipient::factory(random_int(10, 40))->create([
                'whatsapp_broadcast_id' => $broadcastId,
            ]);
        }
    }
}
