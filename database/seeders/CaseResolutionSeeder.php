<?php

namespace Database\Seeders;

use App\Models\CaseResolution;
use Illuminate\Database\Seeder;

class CaseResolutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CaseResolution::factory(60)->create();
    }
}
