<?php

namespace Database\Seeders;

use App\Models\CampaignExecution;
use Illuminate\Database\Seeder;

class CampaignExecutionSeeder extends Seeder
{
    public function run(): void
    {
        CampaignExecution::factory(80)->create();
    }
}
