<?php

namespace Database\Seeders;

use App\Models\WhatsAppBroadcast;
use Illuminate\Database\Seeder;

class WhatsAppBroadcastSeeder extends Seeder
{
    public function run(): void
    {
        WhatsAppBroadcast::factory(25)->create();
    }
}
