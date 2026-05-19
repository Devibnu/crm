<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Database\Seeder;

class CustomerTransactionSeeder extends Seeder
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

        CustomerTransaction::factory(100)->create([
            'customer_id' => fn () => $customerIds->random(),
        ]);
    }
}
