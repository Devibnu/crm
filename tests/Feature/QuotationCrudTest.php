<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_index_is_accessible(): void
    {
        $this->get(route('admin.sales.deals.index'))
            ->assertOk()
            ->assertSee('SALES WORKSPACE')
            ->assertSee('Quotation & Deal')
            ->assertSee('Kelola quotation, penawaran, dan status deal customer.')
            ->assertSee('Add Quotation');
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
            ->assertSee($quotation->title)
            ->assertSee('Sales Workspace')
            ->assertSee('Quote Details')
            ->assertSee('Deal Metadata')
            ->assertSee('Related Records');

        $this->get(route('admin.sales.deals.edit', $quotation))
            ->assertOk()
            ->assertSee('Edit Quotation')
            ->assertSee('Sales Workspace')
            ->assertSee('Deal Context')
            ->assertSee('Quotation Information')
            ->assertSee('Timeline & Notes', false)
            ->assertSee('Simpan Perubahan Quotation?')
            ->assertSee('Ya, Simpan Quotation');

        $this->get(route('admin.sales.deals.create'))
            ->assertOk()
            ->assertSee('Add Quotation')
            ->assertSee('Sales Workspace');
    }

    public function test_quotation_create_prefills_from_opportunity_query(): void
    {
        $customer = Customer::factory()->create(['name' => 'Quotation Prefill Customer']);
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Quotation Prefill Conversation',
            'phone_number' => '628120006666',
            'channel' => 'whatsapp',
            'last_message' => 'Need quotation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Quotation Prefill Lead',
            'conversation_id' => $conversation->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'Quotation Prefill Opportunity',
            'estimated_value' => 99000000,
            'assigned_to' => 'Quotation Owner',
            'notes' => 'Opportunity detail for quotation.',
        ]);

        $this->get(route('admin.sales.quotations.create', ['opportunity_id' => $opportunity->id]))
            ->assertOk()
            ->assertSee('<option value="'.$opportunity->id.'" selected>'.$opportunity->title.'</option>', false)
            ->assertSee('<option value="'.$customer->id.'" selected>'.$customer->name.'</option>', false)
            ->assertSee('name="lead_id" value="'.$lead->id.'"', false)
            ->assertSee('name="conversation_id" value="'.$conversation->id.'"', false)
            ->assertSee('value="Quotation Prefill Opportunity"', false)
            ->assertSee('value="99000000.00"', false)
            ->assertSee('Owner: Quotation Owner')
            ->assertSee('Opportunity Notes: Opportunity detail for quotation.');
    }

    public function test_quotation_created_from_opportunity_keeps_source_links(): void
    {
        $customer = Customer::factory()->create();
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Quotation Source Conversation',
            'phone_number' => '628120007777',
            'channel' => 'whatsapp',
            'last_message' => 'Please quote',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'Quotation Source Opportunity',
        ]);

        $this->post(route('admin.sales.deals.store'), [
            'opportunity_id' => $opportunity->id,
            'lead_id' => null,
            'customer_id' => null,
            'conversation_id' => null,
            'quote_number' => 'QTN-SOURCE-001',
            'title' => 'Quotation Source Opportunity',
            'amount' => 15000000,
            'status' => 'draft',
            'issued_at' => null,
            'valid_until' => null,
            'notes' => 'Created from opportunity.',
        ])->assertRedirect(route('admin.sales.deals.index'));

        $this->assertDatabaseHas('quotations', [
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'quote_number' => 'QTN-SOURCE-001',
        ]);
    }

    public function test_quotation_detail_back_navigation_preserves_opportunity_context(): void
    {
        $opportunity = Opportunity::factory()->create();
        $linkedQuotation = Quotation::factory()->create(['opportunity_id' => $opportunity->id]);
        $standaloneQuotation = Quotation::factory()->create(['opportunity_id' => null]);

        $this->get(route('admin.sales.deals.show', $linkedQuotation))
            ->assertOk()
            ->assertSee('Back to Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false);

        $this->get(route('admin.sales.deals.show', $standaloneQuotation))
            ->assertOk()
            ->assertSee('Opportunity Management')
            ->assertSee(route('admin.sales.opportunities'), false);
    }

    public function test_quotation_detail_shows_source_record_links(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Quotation Detail Conversation',
            'phone_number' => '628120008888',
            'channel' => 'whatsapp',
            'last_message' => 'Detail source',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Quotation Detail Lead',
            'conversation_id' => $conversation->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'title' => 'Quotation Detail Opportunity',
        ]);
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Quotation Detail With Sources',
        ]);

        $this->get(route('admin.sales.deals.show', $quotation))
            ->assertOk()
            ->assertSee('Source Opportunity')
            ->assertSee('Open Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false)
            ->assertSee('Source Lead')
            ->assertSee('Open Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee('Source Conversation')
            ->assertSee('Open Conversation')
            ->assertSee(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]).'#contact', false);
    }

    public function test_quotation_navigation_remains_active_across_quotation_pages(): void
    {
        $quotation = Quotation::factory()->create();
        $activeQuotationNavigation = 'href="'.route('admin.sales.deals.index').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.sales.deals.index'),
            route('admin.sales.deals.create'),
            route('admin.sales.deals.show', $quotation),
            route('admin.sales.deals.edit', $quotation),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeQuotationNavigation, false);
        }
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
        $this->assertNotNull($opportunity->won_at);
    }

    public function test_mark_as_won_action_accepts_quotation_updates_opportunity_and_shows_project_action(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Won Timeline Conversation',
            'phone_number' => '628120009999',
            'channel' => 'whatsapp',
            'last_message' => 'Need proposal',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Won Timeline Lead',
            'conversation_id' => $conversation->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Won Timeline Opportunity',
            'status' => 'negotiation',
            'probability' => 65,
            'estimated_value' => 12000000,
        ]);
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'quote_number' => 'QT-MARK-WON-001',
            'status' => 'sent',
            'amount' => 55500000,
        ]);

        $this->post(route('admin.sales.deals.mark-won', $quotation))
            ->assertRedirect(route('admin.sales.deals.show', $quotation));

        $quotation->refresh();
        $opportunity->refresh();

        $this->assertSame('accepted', $quotation->status);
        $this->assertSame('won', $opportunity->status);
        $this->assertSame(100, $opportunity->probability);
        $this->assertSame('55500000.00', (string) $opportunity->estimated_value);
        $this->assertNotNull($opportunity->won_at);
        $this->assertNull($opportunity->lost_at);
        $this->assertNull($opportunity->lost_reason);

        $this->get(route('admin.sales.deals.show', $quotation))
            ->assertOk()
            ->assertSee('Create Project')
            ->assertSee('Related Project')
            ->assertSee('CRM Timeline')
            ->assertSee('Conversation')
            ->assertSee('Lead')
            ->assertSee('Opportunity')
            ->assertSee('Quotation')
            ->assertSee('Deal Won');
    }

    public function test_mark_as_lost_action_requires_lost_reason(): void
    {
        $opportunity = Opportunity::factory()->create([
            'status' => 'proposal',
        ]);
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'sent',
        ]);

        $this->from(route('admin.sales.deals.show', $quotation))
            ->post(route('admin.sales.deals.mark-lost', $quotation), [])
            ->assertRedirect(route('admin.sales.deals.show', $quotation))
            ->assertSessionHasErrors('lost_reason');

        $this->assertSame('sent', $quotation->refresh()->status);
        $this->assertSame('proposal', $opportunity->refresh()->status);
    }

    public function test_mark_as_lost_action_rejects_quotation_and_sets_opportunity_lost_reason(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Lost Outcome Opportunity',
            'status' => 'negotiation',
            'probability' => 70,
            'estimated_value' => 10000000,
        ]);
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-MARK-LOST-001',
            'status' => 'sent',
            'amount' => 33000000,
        ]);

        $this->post(route('admin.sales.deals.mark-lost', $quotation), [
            'lost_reason' => 'Competitor',
        ])->assertRedirect(route('admin.sales.deals.show', $quotation));

        $quotation->refresh();
        $opportunity->refresh();

        $this->assertSame('rejected', $quotation->status);
        $this->assertSame('lost', $opportunity->status);
        $this->assertSame(0, $opportunity->probability);
        $this->assertSame('33000000.00', (string) $opportunity->estimated_value);
        $this->assertNull($opportunity->won_at);
        $this->assertNotNull($opportunity->lost_at);
        $this->assertSame('Competitor', $opportunity->lost_reason);

        $this->get(route('admin.sales.deals.show', $quotation))
            ->assertOk()
            ->assertSee('Deal Lost')
            ->assertSee('Lost Reason: Competitor')
            ->assertSee('Related Project')
            ->assertSee('Available after Won');
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
            ->assertSee('Related Quotations')
            ->assertSee($quotation->quote_number)
            ->assertSee('Open Quotation')
            ->assertSee(route('admin.sales.deals.show', $quotation), false);
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
