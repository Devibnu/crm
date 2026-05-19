<?php

namespace Database\Seeders;

use App\Models\Quotation;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quotation::factory()
            ->count(80)
            ->sequence(fn ($sequence) => [
                'quote_number' => sprintf('QTN-%s-%04d', now()->format('Y'), $sequence->index + 1),
            ])
            ->create();
    }
}
