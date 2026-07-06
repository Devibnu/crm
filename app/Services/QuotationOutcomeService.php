<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationOutcomeService
{
    /**
     * @return array<int, string>
     */
    public static function lostReasons(): array
    {
        return ['Budget', 'Competitor', 'No Response', 'Cancelled', 'Other'];
    }

    public function handle(Quotation $quotation): void
    {
        if ($quotation->status === 'accepted') {
            $this->markOpportunityWon($quotation);
        }

        if (in_array($quotation->status, ['rejected', 'expired'], true)) {
            $this->markOpportunityLost($quotation, $quotation->status);
        }
    }

    public function markWon(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            $quotation->update([
                'status' => 'accepted',
            ]);

            $this->markOpportunityWon($quotation);

            return $quotation->refresh();
        });
    }

    public function markLost(Quotation $quotation, string $lostReason): Quotation
    {
        return DB::transaction(function () use ($quotation, $lostReason) {
            $quotation->update([
                'status' => 'rejected',
            ]);

            $quotation->load('opportunity');

            $this->markOpportunityLost($quotation, $lostReason);

            return $quotation->refresh();
        });
    }

    protected function markOpportunityWon(Quotation $quotation): void
    {
        $quotation->load('opportunity.lead');

        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return;
        }

        $opportunity->update([
            'status' => 'won',
            'probability' => 100,
            'estimated_value' => $quotation->amount,
            'won_at' => $opportunity->won_at ?: now(),
            'lost_at' => null,
            'lost_reason' => null,
        ]);

        if ($opportunity->lead && $opportunity->lead->status !== 'converted') {
            $opportunity->lead->update([
                'status' => 'converted',
            ]);
        }
    }

    protected function markOpportunityLost(Quotation $quotation, string $lostReason): void
    {
        $quotation->load('opportunity');

        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return;
        }

        $opportunity->update([
            'status' => 'lost',
            'probability' => 0,
            'estimated_value' => $quotation->amount,
            'won_at' => null,
            'lost_at' => $opportunity->lost_at ?: now(),
            'lost_reason' => $lostReason,
        ]);
    }
}
