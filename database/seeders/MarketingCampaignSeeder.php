<?php

namespace Database\Seeders;

use App\Models\MarketingCampaign;
use Illuminate\Database\Seeder;

class MarketingCampaignSeeder extends Seeder
{
    public function run(): void
    {
        MarketingCampaign::factory(60)->create();
    }
}
