<?php

namespace Database\Seeders;

use App\Models\SocialMediaEngagement;
use Illuminate\Database\Seeder;

class SocialMediaEngagementSeeder extends Seeder
{
    public function run(): void
    {
        SocialMediaEngagement::factory(60)->create();
    }
}
