<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use Illuminate\Database\Seeder;

class CustomerInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerIds = Customer::query()->pluck('id');

        if ($customerIds->isEmpty()) {
            return;
        }

        CustomerInteraction::factory(100)->create([
            'customer_id' => fn () => $customerIds->random(),
        ]);
    }
}
