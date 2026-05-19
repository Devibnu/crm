<?php

namespace Database\Seeders;

use App\Models\OmnichannelMessage;
use Illuminate\Database\Seeder;

class OmnichannelMessageSeeder extends Seeder
{
    public function run(): void
    {
        OmnichannelMessage::factory()->count(120)->create();
    }
}
