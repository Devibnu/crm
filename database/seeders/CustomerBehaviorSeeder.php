<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerBehavior;
use Illuminate\Database\Seeder;

class CustomerBehaviorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::query()
            ->inRandomOrder()
            ->take(40)
            ->get(['id'])
            ->each(function (Customer $customer): void {
                CustomerBehavior::factory()->create([
                    'customer_id' => $customer->id,
                ]);
            });
    }
}
