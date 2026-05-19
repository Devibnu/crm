<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerPreference;
use Illuminate\Database\Seeder;

class CustomerPreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::query()
            ->inRandomOrder()
            ->take(35)
            ->get(['id'])
            ->each(function (Customer $customer): void {
                CustomerPreference::factory()->create([
                    'customer_id' => $customer->id,
                ]);
            });
    }
}
