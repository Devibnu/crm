<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_index_is_accessible(): void
    {
        $this->get(route('admin.sales.deals.index'))
            ->assertOk()
            ->assertSee('Quotation & Deal');
    }

    public function test_quotation_can_be_created(): void
    {
        $opportunity = Opportunity::factory()->create();
        $customer = Customer::factory()->create();

        $payload = [
            'opportunity_id' => $opportunity->id,
            'customer_id' => $customer->id,
            'quote_number' => 'QT-2026-0001',
            'title' => 'Quotation Test Title',
            'amount' => 125000000,
            'status' => 'sent',
            'issued_at' => '2026-05-04',
            'valid_until' => '2026-05-30',
            'notes' => 'Quotation created from feature test.',
        ];

        $response = $this->post(route('admin.sales.deals.store'), $payload);

        $response->assertRedirect(route('admin.sales.deals.index'));

        $this->assertDatabaseHas('quotations', [
            'quote_number' => 'QT-2026-0001',
            'title' => 'Quotation Test Title',
            'status' => 'sent',
        ]);
    }

    public function test_accepted_quotation_created_directly_updates_opportunity_to_won(): void
    {
        $opportunity = Opportunity::factory()->create([
            'status' => 'proposal',
            'probability' => 30,
            'estimated_value' => 15000000,
        ]);

        $response = $this->post(route('admin.sales.deals.store'), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-STORE-ACCEPTED-001',
            'title' => 'Accepted Store Quotation',
            'amount' => 64000000,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => 'Accepted directly from create form.',
        ]);

        $response->assertRedirect(route('admin.sales.deals.index'));

        $opportunity->refresh();

        $this->assertSame('won', $opportunity->status);
        $this->assertSame(100, $opportunity->probability);
        $this->assertSame('64000000.00', (string) $opportunity->estimated_value);
    }

    public function test_quotation_show_and_edit_pages_are_accessible(): void
    {
        $quotation = Quotation::factory()->create();

        $this->get(route('admin.sales.deals.show', $quotation))
            ->assertOk()
            ->assertSee($quotation->title);

        $this->get(route('admin.sales.deals.edit', $quotation))
            ->assertOk()
            ->assertSee('Edit Quotation');
    }

    public function test_quotation_can_be_updated(): void
    {
        $quotation = Quotation::factory()->create([
            'quote_number' => 'QT-BEFORE-001',
            'title' => 'Before Quotation Update',
            'status' => 'draft',
        ]);

        $response = $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => null,
            'customer_id' => null,
            'quote_number' => 'QT-AFTER-001',
            'title' => 'After Quotation Update',
            'amount' => 99000000,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => 'Quotation updated from feature test.',
        ]);

        $response->assertRedirect(route('admin.sales.deals.show', $quotation));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'quote_number' => 'QT-AFTER-001',
            'title' => 'After Quotation Update',
            'status' => 'accepted',
        ]);
    }

    public function test_accepted_quotation_updates_opportunity_to_won(): void
    {
        $opportunity = Opportunity::factory()->create([
            'status' => 'proposal',
            'probability' => 45,
            'estimated_value' => 12000000,
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-ACCEPT-OUTCOME-001',
            'status' => 'sent',
            'amount' => 76500000,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-ACCEPT-OUTCOME-001',
            'title' => $quotation->title,
            'amount' => 76500000,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ])->assertRedirect(route('admin.sales.deals.show', $quotation));

        $opportunity->refresh();

        $this->assertSame('won', $opportunity->status);
    }

    public function test_accepted_quotation_sets_probability_100(): void
    {
        $opportunity = Opportunity::factory()->create([
            'status' => 'negotiation',
            'probability' => 70,
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-ACCEPT-PROBABILITY-001',
            'status' => 'draft',
            'amount' => 88000000,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-ACCEPT-PROBABILITY-001',
            'title' => $quotation->title,
            'amount' => 88000000,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->assertSame(100, $opportunity->refresh()->probability);
    }

    public function test_accepted_quotation_syncs_estimated_value_from_amount(): void
    {
        $opportunity = Opportunity::factory()->create([
            'status' => 'proposal',
            'estimated_value' => 10000000,
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-ACCEPT-VALUE-001',
            'status' => 'sent',
            'amount' => 123456789,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-ACCEPT-VALUE-001',
            'title' => $quotation->title,
            'amount' => 123456789,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->assertSame('123456789.00', (string) $opportunity->refresh()->estimated_value);
    }

    public function test_accepted_quotation_converts_related_lead(): void
    {
        $lead = Lead::factory()->create(['status' => 'qualified']);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'status' => 'proposal',
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-ACCEPT-LEAD-001',
            'status' => 'sent',
            'amount' => 50000000,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-ACCEPT-LEAD-001',
            'title' => $quotation->title,
            'amount' => 50000000,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->assertSame('converted', $lead->refresh()->status);
    }

    public function test_rejected_quotation_does_not_set_opportunity_lost(): void
    {
        $lead = Lead::factory()->create(['status' => 'qualified']);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'status' => 'negotiation',
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-REJECT-NO-LOST-001',
            'status' => 'sent',
            'amount' => 45000000,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-REJECT-NO-LOST-001',
            'title' => $quotation->title,
            'amount' => 45000000,
            'status' => 'rejected',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->assertSame('negotiation', $opportunity->refresh()->status);
        $this->assertSame('qualified', $lead->refresh()->status);
    }

    public function test_expired_quotation_does_not_set_opportunity_lost(): void
    {
        $lead = Lead::factory()->create(['status' => 'qualified']);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'status' => 'proposal',
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-EXPIRED-NO-LOST-001',
            'status' => 'sent',
            'amount' => 47000000,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-EXPIRED-NO-LOST-001',
            'title' => $quotation->title,
            'amount' => 47000000,
            'status' => 'expired',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->assertSame('proposal', $opportunity->refresh()->status);
        $this->assertSame('qualified', $lead->refresh()->status);
    }

    public function test_quotation_can_be_deleted(): void
    {
        $quotation = Quotation::factory()->create();

        $response = $this->delete(route('admin.sales.deals.destroy', $quotation));

        $response->assertRedirect(route('admin.sales.deals.index'));

        $this->assertDatabaseMissing('quotations', [
            'id' => $quotation->id,
        ]);
    }

    public function test_quotation_search_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Customer Search Name']);
        $opportunity = Opportunity::factory()->create(['title' => 'Opportunity Search Title']);

        $match = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-SEARCH-100',
            'title' => 'Alpha Quotation Search',
        ]);

        $other = Quotation::factory()->create([
            'customer_id' => null,
            'opportunity_id' => null,
            'quote_number' => 'QT-OTHER-200',
            'title' => 'Other Quotation Name',
        ]);

        $this->get(route('admin.sales.deals.index', ['q' => 'QT-SEARCH-100']))
            ->assertOk()
            ->assertSee($match->quote_number)
            ->assertDontSee($other->quote_number);

        $this->get(route('admin.sales.deals.index', ['q' => 'Customer Search Name']))
            ->assertOk()
            ->assertSee($match->quote_number)
            ->assertDontSee($other->quote_number);

        $this->get(route('admin.sales.deals.index', ['q' => 'Opportunity Search Title']))
            ->assertOk()
            ->assertSee($match->quote_number)
            ->assertDontSee($other->quote_number);
    }

    public function test_quotation_status_filter_works(): void
    {
        $sent = Quotation::factory()->create([
            'quote_number' => 'QT-SENT-001',
            'status' => 'sent',
        ]);

        $draft = Quotation::factory()->create([
            'quote_number' => 'QT-DRAFT-001',
            'status' => 'draft',
        ]);

        $this->get(route('admin.sales.deals.index', ['status' => 'sent']))
            ->assertOk()
            ->assertSee($sent->quote_number)
            ->assertDontSee($draft->quote_number);
    }

    public function test_opportunity_show_displays_recent_quotation(): void
    {
        $opportunity = Opportunity::factory()->create();
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-OPP-001',
            'status' => 'expired',
        ]);

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Recent Quotations')
            ->assertSee($quotation->quote_number)
            ->assertSee('Create Quotation')
            ->assertSee(route('admin.sales.opportunities.create-quotation', $opportunity), false);
    }

    public function test_opportunity_show_displays_open_quotation_when_active_quotation_exists(): void
    {
        $opportunity = Opportunity::factory()->create();
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-ACTIVE-001',
            'status' => 'sent',
        ]);

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Open Quotation')
            ->assertSee(route('admin.sales.deals.show', $quotation), false)
            ->assertDontSee('Create Quotation');
    }

    public function test_opportunity_can_create_draft_quotation(): void
    {
        $customer = Customer::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'title' => 'Managed Firewall Renewal',
            'estimated_value' => 45000000,
            'company_name' => 'Krakatau Partner',
            'contact_name' => 'Budi Sales',
            'notes' => 'Needs bundled implementation service.',
        ]);

        $response = $this->post(route('admin.sales.opportunities.create-quotation', $opportunity));

        $quotation = Quotation::query()->where('opportunity_id', $opportunity->id)->first();

        $this->assertNotNull($quotation);
        $response->assertRedirect(route('admin.sales.deals.show', $quotation));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'opportunity_id' => $opportunity->id,
            'customer_id' => $customer->id,
            'title' => 'Managed Firewall Renewal',
            'amount' => 45000000,
            'status' => 'draft',
        ]);

        $this->assertStringStartsWith('QTN-', $quotation->quote_number);
        $this->assertStringContainsString('Opportunity: Managed Firewall Renewal', $quotation->notes);
    }

    public function test_create_quotation_redirects_to_existing_active_quotation(): void
    {
        $opportunity = Opportunity::factory()->create();
        $activeQuotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'accepted',
        ]);

        $response = $this->post(route('admin.sales.opportunities.create-quotation', $opportunity));

        $response->assertRedirect(route('admin.sales.deals.show', $activeQuotation));
        $this->assertSame(1, Quotation::query()->where('opportunity_id', $opportunity->id)->count());
    }

    public function test_create_quotation_allows_new_draft_after_rejected_or_expired_quotation(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Replacement License Deal',
            'estimated_value' => 17000000,
        ]);

        Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-REJECTED-001',
            'status' => 'rejected',
        ]);

        Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-EXPIRED-001',
            'status' => 'expired',
        ]);

        $response = $this->post(route('admin.sales.opportunities.create-quotation', $opportunity));

        $newQuotation = Quotation::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('status', 'draft')
            ->first();

        $this->assertNotNull($newQuotation);
        $response->assertRedirect(route('admin.sales.deals.show', $newQuotation));
        $this->assertSame(3, Quotation::query()->where('opportunity_id', $opportunity->id)->count());
    }

    public function test_customer_show_displays_recent_quotation(): void
    {
        $customer = Customer::factory()->create();
        $quotation = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'quote_number' => 'QT-CUST-001',
            'title' => 'Customer Deal Quotation',
        ]);

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Recent Quotations / Deals')
            ->assertSee($quotation->quote_number)
            ->assertSee($quotation->title);
    }
}
