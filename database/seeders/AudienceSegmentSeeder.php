<?php

namespace Database\Seeders;

use App\Models\AudienceSegment;
use Illuminate\Database\Seeder;

class AudienceSegmentSeeder extends Seeder
{
    public function run(): void
    {
        AudienceSegment::factory(40)->create();
    }
}
