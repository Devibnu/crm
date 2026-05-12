<?php

namespace Database\Seeders;

use App\Models\WhatsAppBroadcastReply;
use Illuminate\Database\Seeder;

class WhatsAppBroadcastReplySeeder extends Seeder
{
    public function run(): void
    {
        WhatsAppBroadcastReply::factory(120)->create();
    }
}
