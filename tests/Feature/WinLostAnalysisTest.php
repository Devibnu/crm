<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WinLostAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_win_lost_page_is_accessible(): void
    {
        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee('Win/Lost Analysis')
            ->assertSee('Analisa hasil deal');
    }

    public function test_summary_cards_are_displayed(): void
    {
        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee('Won Deals')
            ->assertSee('Lost Deals')
            ->assertSee('Win Rate')
            ->assertSee('Won Value')
            ->assertSee('Lost Value')
            ->assertSee('Quote Acceptance Rate');
    }

    public function test_opportunity_won_and_lost_are_displayed(): void
    {
        $won = Opportunity::factory()->create([
            'title' => 'Won Enterprise Rollout',
            'status' => 'won',
            'expected_close_date' => '2026-05-12',
        ]);
        $lost = Opportunity::factory()->create([
            'title' => 'Lost Renewal Deal',
            'status' => 'lost',
            'expected_close_date' => '2026-05-13',
        ]);
        $open = Opportunity::factory()->create([
            'title' => 'Open Discovery Deal',
            'status' => 'open',
        ]);

        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee($won->title)
            ->assertSee($lost->title)
            ->assertDontSee($open->title);
    }

    public function test_quotation_final_statuses_are_displayed(): void
    {
        $accepted = Quotation::factory()->create(['quote_number' => 'QTN-ACCEPTED-001', 'status' => 'accepted']);
        $rejected = Quotation::factory()->create(['quote_number' => 'QTN-REJECTED-001', 'status' => 'rejected']);
        $expired = Quotation::factory()->create(['quote_number' => 'QTN-EXPIRED-001', 'status' => 'expired']);
        $draft = Quotation::factory()->create(['quote_number' => 'QTN-DRAFT-001', 'status' => 'draft']);

        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee($accepted->quote_number)
            ->assertSee($rejected->quote_number)
            ->assertSee($expired->quote_number)
            ->assertDontSee($draft->quote_number);
    }

    public function test_filter_status_won_works(): void
    {
        $won = Opportunity::factory()->create(['title' => 'Won Status Filter', 'status' => 'won']);
        $lost = Opportunity::factory()->create(['title' => 'Lost Status Filter', 'status' => 'lost']);
        $accepted = Quotation::factory()->create(['opportunity_id' => null, 'quote_number' => 'QTN-WON-FILTER', 'status' => 'accepted']);
        $rejected = Quotation::factory()->create(['opportunity_id' => null, 'quote_number' => 'QTN-LOST-FILTER', 'status' => 'rejected']);

        $this->get(route('admin.sales.win-loss', ['status' => 'won']))
            ->assertOk()
            ->assertSee($won->title)
            ->assertSee($accepted->quote_number)
            ->assertDontSee($lost->title)
            ->assertDontSee($rejected->quote_number);
    }

    public function test_filter_status_lost_works(): void
    {
        $won = Opportunity::factory()->create(['title' => 'Won Lost Filter', 'status' => 'won']);
        $lost = Opportunity::factory()->create(['title' => 'Lost Lost Filter', 'status' => 'lost']);
        $accepted = Quotation::factory()->create(['opportunity_id' => null, 'quote_number' => 'QTN-ACCEPTED-FILTER', 'status' => 'accepted']);
        $expired = Quotation::factory()->create(['opportunity_id' => null, 'quote_number' => 'QTN-EXPIRED-FILTER', 'status' => 'expired']);

        $this->get(route('admin.sales.win-loss', ['status' => 'lost']))
            ->assertOk()
            ->assertSee($lost->title)
            ->assertSee($expired->quote_number)
            ->assertDontSee($won->title)
            ->assertDontSee($accepted->quote_number);
    }

    public function test_filter_assigned_to_works(): void
    {
        $matchOpportunity = Opportunity::factory()->create([
            'title' => 'Matched Owner Opportunity',
            'status' => 'won',
            'assigned_to' => 'Ayu Sales',
        ]);
        $otherOpportunity = Opportunity::factory()->create([
            'title' => 'Other Owner Opportunity',
            'status' => 'won',
            'assigned_to' => 'Bima Sales',
        ]);
        $matchQuotation = Quotation::factory()->create([
            'opportunity_id' => $matchOpportunity->id,
            'quote_number' => 'QTN-OWNER-MATCH',
            'status' => 'accepted',
        ]);
        $otherQuotation = Quotation::factory()->create([
            'opportunity_id' => $otherOpportunity->id,
            'quote_number' => 'QTN-OWNER-OTHER',
            'status' => 'accepted',
        ]);

        $this->get(route('admin.sales.win-loss', ['assigned_to' => 'Ayu Sales']))
            ->assertOk()
            ->assertSee($matchOpportunity->title)
            ->assertSee($matchQuotation->quote_number)
            ->assertDontSee($otherOpportunity->title)
            ->assertDontSee($otherQuotation->quote_number);
    }

    public function test_filter_date_range_works(): void
    {
        $inside = Opportunity::factory()->create([
            'title' => 'Inside Date Range',
            'status' => 'won',
            'expected_close_date' => '2026-05-15',
        ]);
        $outside = Opportunity::factory()->create([
            'title' => 'Outside Date Range',
            'status' => 'lost',
            'expected_close_date' => '2026-06-15',
        ]);
        $insideQuotation = Quotation::factory()->create([
            'opportunity_id' => null,
            'quote_number' => 'QTN-DATE-INSIDE',
            'status' => 'accepted',
            'valid_until' => '2026-05-20',
            'issued_at' => '2026-04-01',
        ]);
        $outsideQuotation = Quotation::factory()->create([
            'opportunity_id' => null,
            'quote_number' => 'QTN-DATE-OUTSIDE',
            'status' => 'accepted',
            'valid_until' => '2026-06-20',
            'issued_at' => '2026-05-10',
        ]);

        $this->get(route('admin.sales.win-loss', [
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]))
            ->assertOk()
            ->assertSee($inside->title)
            ->assertSee($insideQuotation->quote_number)
            ->assertDontSee($outside->title)
            ->assertDontSee($outsideQuotation->quote_number);
    }

    public function test_win_rate_is_calculated_correctly_with_controlled_data(): void
    {
        Opportunity::factory()->create(['status' => 'won', 'estimated_value' => 100000]);
        Opportunity::factory()->create(['status' => 'won', 'estimated_value' => 200000]);
        Opportunity::factory()->create(['status' => 'lost', 'estimated_value' => 50000]);

        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee('66.67%')
            ->assertSee('33.33%')
            ->assertSee('Rp 300.000,00')
            ->assertSee('Rp 50.000,00');
    }

    public function test_quote_acceptance_rate_is_calculated_correctly_with_controlled_data(): void
    {
        Customer::factory()->create();
        Quotation::factory()->create(['status' => 'accepted', 'amount' => 100000]);
        Quotation::factory()->create(['status' => 'accepted', 'amount' => 200000]);
        Quotation::factory()->create(['status' => 'rejected', 'amount' => 50000]);
        Quotation::factory()->create(['status' => 'expired', 'amount' => 25000]);

        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee('50.00%')
            ->assertSee('2 accepted of 4 final quotes');
    }

    public function test_win_lost_analysis_sees_accepted_opportunity_as_won(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Accepted Win Loss Opportunity',
            'status' => 'proposal',
            'estimated_value' => 1000000,
            'probability' => 40,
            'expected_close_date' => '2026-06-20',
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-WINLOSS-WON-001',
            'status' => 'sent',
            'amount' => 87654321,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-WINLOSS-WON-001',
            'title' => $quotation->title,
            'amount' => 87654321,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->get(route('admin.sales.win-loss'))
            ->assertOk()
            ->assertSee('Accepted Win Loss Opportunity')
            ->assertSee('Rp 87.654.321,00');
    }
}
