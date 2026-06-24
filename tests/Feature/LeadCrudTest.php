<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_index_is_accessible(): void
    {
        $this->get(route('admin.sales.leads'))
            ->assertOk()
            ->assertSee('Lead Management');
    }

    public function test_lead_index_uses_custom_delete_confirmation_modal(): void
    {
        $lead = Lead::factory()->create(['name' => 'Lead Modal Confirmation']);

        $this->get(route('admin.sales.leads'))
            ->assertOk()
            ->assertSee('Hapus Lead?')
            ->assertSee('Ya, Hapus Lead')
            ->assertSee('data-delete-action="'.route('admin.sales.leads.destroy', $lead).'"', false)
            ->assertSee('data-lead-name="Lead Modal Confirmation"', false)
            ->assertDontSee("confirm('Delete lead ini?')", false);
    }

    public function test_lead_index_supports_whitelisted_per_page_values(): void
    {
        foreach (range(1, 120) as $index) {
            Lead::factory()->create([
                'name' => sprintf('Pagination Match Lead %03d', $index),
                'status' => 'new',
                'priority' => 'high',
            ]);
        }

        foreach ([10, 20, 50, 100] as $perPage) {
            $this->get(route('admin.sales.leads', ['per_page' => $perPage]))
                ->assertOk()
                ->assertViewHas('leads', fn ($leads) => $leads->perPage() === $perPage && $leads->count() === $perPage)
                ->assertSee('<option value="'.$perPage.'" selected>Show '.$perPage.'</option>', false);
        }

        $this->get(route('admin.sales.leads', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->perPage() === 10 && $leads->count() === 10)
            ->assertSee('<option value="10" selected>Show 10</option>', false);

        $this->get(route('admin.sales.leads', [
            'q' => 'Pagination Match',
            'status' => 'new',
            'priority' => 'high',
            'per_page' => 20,
        ]))
            ->assertOk()
            ->assertViewHas('leads', function ($leads) {
                parse_str((string) parse_url($leads->nextPageUrl(), PHP_URL_QUERY), $query);

                return $leads->perPage() === 20
                    && $leads->count() === 20
                    && ($query['q'] ?? null) === 'Pagination Match'
                    && ($query['status'] ?? null) === 'new'
                    && ($query['priority'] ?? null) === 'high'
                    && ($query['per_page'] ?? null) === '20';
            });
    }

    public function test_lead_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'name' => 'Lead Test Name',
            'company_name' => 'Lead Test Company',
            'email' => 'lead-create@example.com',
            'phone' => '081111111111',
            'source' => 'Website',
            'status' => 'new',
            'priority' => 'high',
            'assigned_to' => 'Sales One',
            'notes' => 'Lead created from feature test.',
        ];

        $response = $this->post(route('admin.sales.leads.store'), $payload);

        $response->assertRedirect(route('admin.sales.leads'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Lead Test Name',
            'status' => 'new',
            'priority' => 'high',
        ]);
    }

    public function test_lead_create_page_uses_sales_workspace_layout(): void
    {
        $this->get(route('admin.sales.leads.create'))
            ->assertOk()
            ->assertSee('Sales Workspace')
            ->assertSee('Lead Summary')
            ->assertSee('Lead Workflow')
            ->assertSee('Best Practices')
            ->assertSee('Save Lead');
    }

    public function test_lead_create_prefills_from_whatsapp_conversation(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Fallback Customer Name',
            'company_name' => 'Prefill Customer Co',
        ]);
        $conversation = WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'contact_name' => 'WhatsApp Prefill Contact',
            'phone_number' => '6281234567890',
            'channel' => 'whatsapp',
            'last_message' => 'Saya mau tanya harga',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->get(route('admin.sales.leads.create', ['conversation_id' => $conversation->id]))
            ->assertOk()
            ->assertSee('name="conversation_id" value="'.$conversation->id.'"', false)
            ->assertSee('value="WhatsApp Prefill Contact"', false)
            ->assertSee('value="6281234567890"', false)
            ->assertSee('value="Prefill Customer Co"', false)
            ->assertSee('value="whatsapp"', false)
            ->assertSee('<option value="new" selected>New</option>', false)
            ->assertSee('value="'.auth()->user()->name.'"', false)
            ->assertSee('Created from WhatsApp conversation #'.$conversation->id.'. Last message: Saya mau tanya harga');
    }

    public function test_lead_created_from_whatsapp_conversation_keeps_source_reference(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Stored Source Contact',
            'phone_number' => '628120001111',
            'channel' => 'whatsapp',
            'last_message' => 'Butuh follow up sales',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->post(route('admin.sales.leads.store'), [
            'conversation_id' => $conversation->id,
            'customer_id' => null,
            'name' => 'Stored Source Contact',
            'company_name' => null,
            'email' => null,
            'phone' => '628120001111',
            'source' => 'whatsapp',
            'status' => 'new',
            'priority' => 'medium',
            'assigned_to' => auth()->user()->name,
            'notes' => 'Created from WhatsApp conversation #'.$conversation->id.'. Last message: Butuh follow up sales',
        ]);

        $response->assertRedirect(route('admin.sales.leads'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Stored Source Contact',
            'conversation_id' => $conversation->id,
            'source_whatsapp_conversation_id' => $conversation->id,
            'source' => 'whatsapp',
        ]);
    }

    public function test_lead_show_and_edit_pages_are_accessible(): void
    {
        $lead = Lead::factory()->create();

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee($lead->name)
            ->assertSee('Sales Workspace')
            ->assertSee('Back to Lead Management');

        $this->get(route('admin.sales.leads.edit', $lead))
            ->assertOk()
            ->assertSee('Edit Lead')
            ->assertSee('Sales Workspace')
            ->assertSee('Lead Identity')
            ->assertSee('Contact Information')
            ->assertSee('Sales Qualification');
    }

    public function test_lead_show_displays_open_conversation_link(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Lead Detail Conversation',
            'phone_number' => '628120002222',
            'channel' => 'whatsapp',
            'last_message' => 'Detail page message',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Conversation Linked Lead',
            'conversation_id' => $conversation->id,
        ]);

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee('Source Conversation')
            ->assertSee('Lead Detail Conversation')
            ->assertSee('Open Conversation')
            ->assertSee(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]).'#contact', false);
    }

    public function test_lead_edit_uses_custom_update_confirmation_modal(): void
    {
        $lead = Lead::factory()->create(['name' => 'Lead Update Confirmation']);

        $this->get(route('admin.sales.leads.edit', $lead))
            ->assertOk()
            ->assertSee('Simpan Perubahan Lead?')
            ->assertSee('Pastikan data lead sudah benar sebelum disimpan.')
            ->assertSee('Lead Update Confirmation')
            ->assertSee('Ya, Simpan Perubahan')
            ->assertSee('data-lead-update-form', false);
    }

    public function test_lead_navigation_remains_active_across_lead_workspace_pages(): void
    {
        $lead = Lead::factory()->create();
        $activeLeadNavigation = 'href="'.route('admin.sales.leads').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.sales.leads'),
            route('admin.sales.leads.create'),
            route('admin.sales.leads.show', $lead),
            route('admin.sales.leads.edit', $lead),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeLeadNavigation, false);
        }
    }

    public function test_lead_index_and_detail_display_score_temperature_and_sources(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Qualified WhatsApp Contact',
            'phone_number' => '6281200099999',
            'channel' => 'whatsapp',
            'last_message' => 'Minta penawaran dan proposal',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Hot WhatsApp Lead',
            'phone' => '6281200099999',
            'whatsapp' => '6281200099999',
            'lead_score' => 65,
            'lead_temperature' => 'hot',
            'lead_score_breakdown' => [
                ['label' => 'Reply Broadcast', 'points' => 5],
                ['label' => 'Keyword: minta penawaran', 'points' => 30],
                ['label' => 'Keyword: proposal', 'points' => 30],
            ],
            'source_campaign' => 'Promo Qualification Campaign',
            'source_whatsapp_conversation_id' => $conversation->id,
        ]);

        $this->get(route('admin.sales.leads'))
            ->assertOk()
            ->assertSee('Hot WhatsApp Lead')
            ->assertSee('Qualified Leads')
            ->assertSee('Status & Priority', false);

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee('Score 65')
            ->assertSee('Hot')
            ->assertSee('Score Breakdown')
            ->assertSee('Reply Broadcast')
            ->assertSee('+5')
            ->assertSee('Keyword: minta penawaran')
            ->assertSee('+30')
            ->assertSee('Promo Qualification Campaign')
            ->assertSee('Qualified WhatsApp Contact')
            ->assertSee('/admin/service/omnichannel?conversation='.$conversation->id, false);
    }

    public function test_hot_lead_can_be_converted_to_opportunity(): void
    {
        $customer = Customer::factory()->create();
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Convert Contact',
            'phone_number' => '6281200012345',
            'channel' => 'whatsapp',
            'last_message' => 'Minta penawaran',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Hot Convert Lead',
            'company_name' => 'Hot Convert Co',
            'assigned_to' => 'Sales Convert',
            'lead_score' => 75,
            'lead_temperature' => 'hot',
            'source_campaign' => 'Promo Convert Campaign',
            'source_whatsapp_conversation_id' => $conversation->id,
        ]);

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee('Convert To Opportunity');

        $response = $this->post(route('admin.sales.leads.convert-to-opportunity', $lead));
        $opportunity = Opportunity::query()->where('lead_id', $lead->id)->firstOrFail();

        $response->assertRedirect(route('admin.sales.opportunities.show', $opportunity));
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'assigned_to' => 'Sales Convert',
            'status' => 'open',
            'probability' => 75,
        ]);
        $this->assertStringContainsString('Source: WhatsApp', $opportunity->notes);
        $this->assertStringContainsString('Campaign: Promo Convert Campaign', $opportunity->notes);
        $this->assertStringContainsString('Lead Score: 75', $opportunity->notes);
        $this->assertStringContainsString('Temperature: Hot', $opportunity->notes);
        $this->assertStringContainsString('WhatsApp Conversation: '.$conversation->id, $opportunity->notes);
    }

    public function test_convert_to_opportunity_reuses_existing_active_opportunity(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Existing Opportunity Lead',
            'lead_temperature' => 'warm',
        ]);
        $existing = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'title' => 'Existing Active Opportunity',
            'status' => 'qualified',
        ]);

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee('Open Opportunity')
            ->assertDontSee('Convert To Opportunity');

        $this->post(route('admin.sales.leads.convert-to-opportunity', $lead))
            ->assertRedirect(route('admin.sales.opportunities.show', $existing));

        $this->assertSame(1, Opportunity::query()->where('lead_id', $lead->id)->count());
    }

    public function test_lead_can_be_updated(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Before Lead Update',
            'email' => 'before-lead-update@example.com',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        $response = $this->put(route('admin.sales.leads.update', $lead), [
            'customer_id' => null,
            'name' => 'After Lead Update',
            'company_name' => 'Updated Lead Co',
            'email' => 'after-lead-update@example.com',
            'phone' => '082222222222',
            'source' => 'Referral',
            'status' => 'qualified',
            'priority' => 'low',
            'assigned_to' => 'Sales Two',
            'notes' => 'Lead updated from feature test.',
        ]);

        $response->assertRedirect(route('admin.sales.leads.show', $lead));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'After Lead Update',
            'status' => 'qualified',
            'priority' => 'low',
        ]);
    }

    public function test_lead_update_keeps_existing_validation_rules(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Lead Validation Baseline',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        $this->from(route('admin.sales.leads.edit', $lead))
            ->put(route('admin.sales.leads.update', $lead), [
                'name' => '',
                'email' => 'not-an-email',
                'status' => 'invalid-status',
                'priority' => 'invalid-priority',
            ])
            ->assertRedirect(route('admin.sales.leads.edit', $lead))
            ->assertSessionHasErrors(['name', 'email', 'status', 'priority']);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Lead Validation Baseline',
            'status' => 'new',
            'priority' => 'medium',
        ]);
    }

    public function test_lead_can_be_deleted(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('admin.sales.leads.destroy', $lead));

        $response->assertRedirect(route('admin.sales.leads'));

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_lead_search_works(): void
    {
        $match = Lead::factory()->create([
            'name' => 'Alpha Lead Search',
            'company_name' => 'Search Corp',
            'email' => 'search-lead@example.com',
            'phone' => '083333333333',
            'assigned_to' => 'Search Person',
        ]);

        $other = Lead::factory()->create([
            'name' => 'Other Lead Name',
            'company_name' => 'Other Corp',
            'email' => 'other-lead@example.com',
        ]);

        $this->get(route('admin.sales.leads', ['q' => 'Alpha Lead Search']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);

        $this->get(route('admin.sales.leads', ['q' => 'Search Person']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_lead_status_filter_works(): void
    {
        $qualified = Lead::factory()->create([
            'name' => 'Qualified Lead',
            'status' => 'qualified',
        ]);

        $unqualified = Lead::factory()->create([
            'name' => 'Unqualified Lead',
            'status' => 'unqualified',
        ]);

        $this->get(route('admin.sales.leads', ['status' => 'qualified']))
            ->assertOk()
            ->assertSee($qualified->name)
            ->assertDontSee($unqualified->name);
    }

    public function test_lead_priority_filter_works(): void
    {
        $high = Lead::factory()->create([
            'name' => 'High Priority Lead',
            'priority' => 'high',
        ]);

        $low = Lead::factory()->create([
            'name' => 'Low Priority Lead',
            'priority' => 'low',
        ]);

        $this->get(route('admin.sales.leads', ['priority' => 'high']))
            ->assertOk()
            ->assertSee($high->name)
            ->assertDontSee($low->name);
    }
}
