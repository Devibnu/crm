<?php

namespace Database\Seeders;

use App\Models\LeadScoringRule;
use Illuminate\Database\Seeder;

class LeadScoringRuleSeeder extends Seeder
{
    public function run(): void
    {
        LeadScoringRule::factory(40)->create();
    }
}
