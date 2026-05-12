<?php

namespace Database\Seeders;

use App\Models\MarketingAutomation;
use Illuminate\Database\Seeder;

class MarketingAutomationSeeder extends Seeder
{
    public function run(): void
    {
        MarketingAutomation::factory(50)->create();
    }
}
