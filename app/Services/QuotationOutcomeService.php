<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationOutcomeService
{
    public function handle(Quotation $quotation): void
    {
        if ($quotation->status !== 'accepted') {
            return;
        }

        $quotation->load('opportunity.lead');

        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return;
        }

        $opportunity->update([
            'status' => 'won',
            'probability' => 100,
            'estimated_value' => $quotation->amount,
        ]);

        if ($opportunity->lead && $opportunity->lead->status !== 'converted') {
            $opportunity->lead->update([
                'status' => 'converted',
            ]);
        }
    }
}
