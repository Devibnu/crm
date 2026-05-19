<?php

namespace Database\Seeders;

use App\Models\CustomerSatisfaction;
use Illuminate\Database\Seeder;

class CustomerSatisfactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerSatisfaction::factory(80)->create();
    }
}
