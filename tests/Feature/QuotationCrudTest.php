<?php

namespace Tests\Feature;

use App\Models\Customer;
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
        ]);

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Recent Quotations')
            ->assertSee($quotation->quote_number)
            ->assertSee(route('admin.sales.deals.create', ['opportunity_id' => $opportunity->id]), false);
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
