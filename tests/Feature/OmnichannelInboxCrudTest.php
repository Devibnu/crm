<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\OmnichannelMessage;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmnichannelInboxCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_omnichannel_inbox_index_is_accessible(): void
    {
        $this->get(route('admin.service.omnichannel.index'))
            ->assertOk()
            ->assertSee('Omnichannel Inbox')
            ->assertSee('Inbox percakapan WhatsApp real dari webhook Meta Cloud API.')
            ->assertSee('data-omni-profile-tab="contact"', false)
            ->assertSee('data-omni-profile-tab="crm"', false)
            ->assertSee("profileTabStorageKey = 'krakatau.omnichannel.profileTab'", false)
            ->assertSee('window.location.hash.slice(1)', false)
            ->assertSee('data-poll-url=', false)
            ->assertSee('pollOmnichannel', false)
            ->assertDontSee('window.location.reload', false);
    }

    public function test_omnichannel_message_can_be_created(): void
    {
        $customer = Customer::factory()->create(['name' => 'Omni Customer']);

        $response = $this->post(route('admin.service.omnichannel.store'), [
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'sender_name' => 'Omni Sender',
            'sender_contact' => '+628123456789',
            'subject' => 'Need delivery update',
            'message' => 'Can you check my latest delivery status?',
            'status' => 'unread',
            'assigned_to' => 'Support Agent',
            'received_at' => '2026-05-20T09:00',
            'resolved_at' => null,
        ]);

        $message = OmnichannelMessage::query()->where('subject', 'Need delivery update')->firstOrFail();

        $response->assertRedirect(route('admin.service.omnichannel.show', $message));

        $this->assertDatabaseHas('omnichannel_messages', [
            'id' => $message->id,
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'status' => 'unread',
            'assigned_to' => 'Support Agent',
        ]);
    }

    public function test_omnichannel_show_and_edit_pages_are_accessible(): void
    {
        $message = OmnichannelMessage::factory()->create([
            'subject' => 'Accessible Omni Message',
        ]);

        $this->get(route('admin.service.omnichannel.show', $message))
            ->assertOk()
            ->assertSee('Accessible Omni Message');

        $this->get(route('admin.service.omnichannel.edit', $message))
            ->assertOk()
            ->assertSee('Edit Omnichannel Message');
    }

    public function test_omnichannel_message_can_be_updated(): void
    {
        $message = OmnichannelMessage::factory()->create([
            'channel' => 'email',
            'direction' => 'inbound',
            'status' => 'pending',
        ]);

        $response = $this->put(route('admin.service.omnichannel.update', $message), [
            'customer_id' => null,
            'channel' => 'instagram',
            'direction' => 'outbound',
            'sender_name' => 'Updated Omni Sender',
            'sender_contact' => '@updatedsender',
            'subject' => 'Updated omnichannel subject',
            'message' => 'Updated message body for omnichannel inbox.',
            'status' => 'resolved',
            'assigned_to' => 'Updated Agent',
            'received_at' => '2026-05-20T10:00',
            'resolved_at' => '2026-05-20T11:00',
        ]);

        $response->assertRedirect(route('admin.service.omnichannel.show', $message));

        $this->assertDatabaseHas('omnichannel_messages', [
            'id' => $message->id,
            'channel' => 'instagram',
            'direction' => 'outbound',
            'status' => 'resolved',
            'assigned_to' => 'Updated Agent',
        ]);
    }

    public function test_omnichannel_message_can_be_deleted(): void
    {
        $message = OmnichannelMessage::factory()->create();

        $response = $this->delete(route('admin.service.omnichannel.destroy', $message));

        $response->assertRedirect(route('admin.service.omnichannel.index'));

        $this->assertDatabaseMissing('omnichannel_messages', [
            'id' => $message->id,
        ]);
    }

    public function test_omnichannel_search_works(): void
    {
        $match = WhatsAppConversation::query()->create([
            'contact_name' => 'Unique Omni Search Sender',
            'phone_number' => '+628111111111',
            'channel' => 'whatsapp',
            'last_message' => 'Searchable WhatsApp conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $match->id,
            'phone' => '+628111111111',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Searchable WhatsApp conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $other = WhatsAppConversation::query()->create([
            'contact_name' => 'Different Sender',
            'phone_number' => '+628222222222',
            'channel' => 'whatsapp',
            'last_message' => 'Different WhatsApp conversation',
            'last_message_at' => now()->subMinute(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $other->id,
            'phone' => '+628222222222',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Different WhatsApp conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['q' => 'Unique Omni Search']))
            ->assertOk()
            ->assertSee($match->contact_name)
            ->assertDontSee($other->contact_name);
    }

    public function test_omnichannel_index_uses_whatsapp_conversations_not_legacy_messages(): void
    {
        $conversation = WhatsAppConversation::query()->create([
            'contact_name' => 'Real WhatsApp Sender',
            'phone_number' => '+628333333333',
            'channel' => 'whatsapp',
            'last_message' => 'Real database conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '+628333333333',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Real database conversation',
            'provider' => 'fonnte',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $legacy = OmnichannelMessage::factory()->create([
            'sender_name' => 'Legacy Channel Sender',
            'channel' => 'email',
        ]);

        $this->get(route('admin.service.omnichannel.index'))
            ->assertOk()
            ->assertSee($conversation->contact_name)
            ->assertDontSee($legacy->sender_name);
    }

    public function test_omnichannel_status_filter_works(): void
    {
        $open = WhatsAppConversation::query()->create([
            'contact_name' => 'Open Status Sender',
            'phone_number' => '+628444444444',
            'channel' => 'whatsapp',
            'last_message' => 'Open conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $open->id,
            'phone' => '+628444444444',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Open conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $pending = WhatsAppConversation::query()->create([
            'contact_name' => 'Pending Status Sender',
            'phone_number' => '+628555555555',
            'channel' => 'whatsapp',
            'last_message' => 'Pending conversation',
            'last_message_at' => now()->subMinute(),
            'status' => 'pending',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $pending->id,
            'phone' => '+628555555555',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Pending conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee($open->contact_name)
            ->assertDontSee($pending->contact_name);
    }

    public function test_omnichannel_poll_endpoint_returns_conversations_messages_and_workspace(): void
    {
        $conversation = WhatsAppConversation::query()->create([
            'contact_name' => 'Polling Contact',
            'phone_number' => '+628777777777',
            'channel' => 'whatsapp',
            'last_message' => 'Latest polling message',
            'last_message_at' => now(),
            'status' => 'open',
            'unread_count' => 2,
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '+628777777777',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Latest polling message',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.selected_conversation_id', $conversation->id)
            ->assertJsonPath('data.selected_conversation.name', 'Polling Contact')
            ->assertJsonPath('data.conversations.0.name', 'Polling Contact')
            ->assertJsonPath('data.messages.0.message', 'Latest polling message')
            ->assertJsonPath('data.workspace.contact.name', 'Polling Contact')
            ->assertJsonPath(
                'data.workspace.contact.lead_create_url',
                route('admin.sales.leads.create', ['conversation_id' => $conversation->id]),
            )
            ->assertJsonPath(
                'data.workspace.contact.ticket_create_url',
                route('admin.service.tickets.create', ['conversation_id' => $conversation->id]),
            );

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $conversation->id,
            'unread_count' => 0,
        ]);
    }

    public function test_omnichannel_workspace_shows_crm_summary_lifecycle_and_anti_duplicate_actions(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Workspace Customer',
            'whatsapp' => '628700000001',
        ]);
        $conversation = WhatsAppConversation::query()->create([
            'customer_id' => $customer->id,
            'contact_name' => 'Workspace Contact',
            'phone_number' => '628700000001',
            'channel' => 'whatsapp',
            'last_message' => 'Need CRM workspace',
            'last_message_at' => now(),
            'status' => 'open',
            'assigned_to' => 'Admin CRM',
            'taken_at' => now()->subMinutes(5),
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'phone' => '628700000001',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need CRM workspace',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'name' => 'Workspace Lead',
            'status' => 'qualified',
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Workspace Opportunity',
            'status' => 'won',
            'estimated_value' => 75000000,
            'won_at' => now(),
        ]);
        $quotation = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'conversation_id' => $conversation->id,
            'quote_number' => 'QTN-OMNI-CRM-001',
            'status' => 'accepted',
            'amount' => 75000000,
        ]);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'ticket_number' => 'TCK-OMNI-CRM-001',
            'subject' => 'Workspace Ticket',
            'status' => 'open',
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Lifecycle Progress')
            ->assertSee('CRM Summary')
            ->assertSee('Conversation Created')
            ->assertSee('Conversation Assigned')
            ->assertSee('Lead Created')
            ->assertSee('Opportunity Created')
            ->assertSee('Quotation Created')
            ->assertSee('Deal Won')
            ->assertSee('Ticket Created')
            ->assertSee('Open Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee('Open Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false)
            ->assertSee('Open Quotation')
            ->assertSee(route('admin.sales.deals.show', $quotation), false)
            ->assertSee('Open Ticket')
            ->assertSee(route('admin.service.tickets.show', $ticket), false)
            ->assertSee('Create Project')
            ->assertSee('Open Customer')
            ->assertSee(route('admin.customers.show', $customer), false)
            ->assertDontSee(route('admin.sales.leads.create', ['conversation_id' => $conversation->id]), false)
            ->assertDontSee(route('admin.sales.opportunities.create', ['lead_id' => $lead->id]), false)
            ->assertDontSee(route('admin.sales.quotations.create', ['opportunity_id' => $opportunity->id]), false);
    }

    public function test_omnichannel_poll_payload_returns_same_crm_workspace_data(): void
    {
        $conversation = WhatsAppConversation::query()->create([
            'contact_name' => 'Payload Contact',
            'phone_number' => '628700000002',
            'channel' => 'whatsapp',
            'last_message' => 'Payload message',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628700000002',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Payload message',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $lead = Lead::factory()->create([
            'conversation_id' => $conversation->id,
            'name' => 'Payload Lead',
        ]);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Payload Opportunity',
            'status' => 'proposal',
            'estimated_value' => 25000000,
        ]);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.workspace.lead.label', 'Payload Lead')
            ->assertJsonPath('data.workspace.opportunity.label', 'Payload Opportunity')
            ->assertJsonPath('data.workspace.quotation', null)
            ->assertJsonPath('data.workspace.ticket', null)
            ->assertJsonPath('data.workspace.lifecycle_step.key', 'opportunity')
            ->assertJsonPath('data.workspace.action_urls.create_lead', null)
            ->assertJsonPath('data.workspace.action_urls.open_lead', route('admin.sales.leads.show', $lead))
            ->assertJsonPath('data.workspace.action_urls.create_opportunity', null)
            ->assertJsonPath('data.workspace.action_urls.open_opportunity', route('admin.sales.opportunities.show', $opportunity))
            ->assertJsonPath('data.workspace.action_urls.create_quotation', route('admin.sales.quotations.create', ['opportunity_id' => $opportunity->id]))
            ->assertJsonPath('data.workspace.crm.summary.lead.label', 'Payload Lead')
            ->assertJsonPath('data.workspace.crm.summary.opportunity.label', 'Payload Opportunity')
            ->assertJsonPath('data.workspace.crm.lifecycle_step.key', 'opportunity')
            ->assertJsonFragment(['label' => 'Conversation Created'])
            ->assertJsonFragment(['label' => 'Lead Created'])
            ->assertJsonFragment(['label' => 'Opportunity Created']);
    }

    public function test_omnichannel_workspace_syncs_opportunity_and_quotation_through_lead_id(): void
    {
        $customer = Customer::factory()->create(['name' => 'Lead Sync Customer']);
        $conversation = WhatsAppConversation::query()->create([
            'customer_id' => $customer->id,
            'contact_name' => 'Lead Sync Contact',
            'phone_number' => '628700000003',
            'channel' => 'whatsapp',
            'last_message' => 'Lead sync message',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'phone' => '628700000003',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Lead sync message',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'name' => 'Lead Sync Lead',
            'status' => 'qualified',
        ]);
        $unrelatedCustomerOpportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => null,
            'conversation_id' => null,
            'title' => 'Newer Customer Opportunity',
            'status' => 'proposal',
            'created_at' => now(),
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => null,
            'lead_id' => $lead->id,
            'conversation_id' => null,
            'title' => 'Lead Linked Opportunity',
            'status' => 'proposal',
            'estimated_value' => 99000000,
            'created_at' => now()->subDay(),
        ]);
        $quotation = Quotation::factory()->create([
            'customer_id' => null,
            'lead_id' => null,
            'opportunity_id' => $opportunity->id,
            'conversation_id' => null,
            'quote_number' => 'QTN-LEAD-SYNC-001',
            'title' => 'Lead Linked Quotation',
            'status' => 'draft',
            'amount' => 99000000,
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Open Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee('Open Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false)
            ->assertSee('Open Quotation')
            ->assertSee(route('admin.sales.deals.show', $quotation), false)
            ->assertDontSee(route('admin.sales.leads.create', ['conversation_id' => $conversation->id]), false)
            ->assertDontSee(route('admin.sales.opportunities.create', ['lead_id' => $lead->id]), false)
            ->assertDontSee(route('admin.sales.quotations.create', ['opportunity_id' => $opportunity->id]), false);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.workspace.lead.label', 'Lead Sync Lead')
            ->assertJsonPath('data.workspace.opportunity.label', 'Lead Linked Opportunity')
            ->assertJsonPath('data.workspace.quotation.label', 'QTN-LEAD-SYNC-001')
            ->assertJsonPath('data.workspace.action_urls.create_lead', null)
            ->assertJsonPath('data.workspace.action_urls.create_opportunity', null)
            ->assertJsonPath('data.workspace.action_urls.create_quotation', null)
            ->assertJsonPath('data.workspace.action_urls.open_opportunity', route('admin.sales.opportunities.show', $opportunity))
            ->assertJsonPath('data.workspace.action_urls.open_quotation', route('admin.sales.deals.show', $quotation))
            ->assertJsonPath('data.workspace.crm.summary.opportunity.label', 'Lead Linked Opportunity')
            ->assertJsonPath('data.workspace.crm.summary.quotation.label', 'QTN-LEAD-SYNC-001')
            ->assertJsonPath('data.workspace.crm.lifecycle_step.key', 'quotation');
    }

    public function test_omnichannel_shows_create_project_only_when_deal_is_won(): void
    {
        ['conversation' => $conversation, 'quotation' => $quotation] = $this->crmWorkspaceWithQuotation('accepted', 'won');

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Open Deal')
            ->assertSee(route('admin.sales.deals.show', $quotation), false)
            ->assertSee('Create Project')
            ->assertSee(route('admin.sales.projects.create', ['quotation_id' => $quotation->id]), false);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.workspace.action_urls.open_deal', route('admin.sales.deals.show', $quotation))
            ->assertJsonPath('data.workspace.action_urls.create_project', route('admin.sales.projects.create', ['quotation_id' => $quotation->id]));
    }

    public function test_omnichannel_draft_quotation_does_not_show_create_project_or_open_deal(): void
    {
        ['conversation' => $conversation] = $this->crmWorkspaceWithQuotation('draft', 'proposal');

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Open Customer')
            ->assertSee('Open Lead')
            ->assertSee('Open Opportunity')
            ->assertSee('Open Quotation')
            ->assertSee('Open Ticket')
            ->assertDontSee(route('admin.sales.projects.create'), false);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.workspace.action_urls.open_deal', null)
            ->assertJsonPath('data.workspace.action_urls.create_project', null);
    }

    public function test_omnichannel_lost_deal_shows_open_deal_without_create_project_and_keeps_existing_actions(): void
    {
        [
            'conversation' => $conversation,
            'customer' => $customer,
            'lead' => $lead,
            'opportunity' => $opportunity,
            'quotation' => $quotation,
            'ticket' => $ticket,
        ] = $this->crmWorkspaceWithQuotation('rejected', 'lost');

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Open Customer')
            ->assertSee(route('admin.customers.show', $customer), false)
            ->assertSee('Open Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee('Open Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false)
            ->assertSee('Open Quotation')
            ->assertSee(route('admin.sales.deals.show', $quotation), false)
            ->assertSee('Open Ticket')
            ->assertSee(route('admin.service.tickets.show', $ticket), false)
            ->assertSee('Open Deal')
            ->assertDontSee(route('admin.sales.projects.create'), false);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.workspace.action_urls.open_deal', route('admin.sales.deals.show', $quotation))
            ->assertJsonPath('data.workspace.action_urls.create_project', null);
    }

    /**
     * @return array{customer:Customer,lead:Lead,opportunity:Opportunity,quotation:Quotation,ticket:Ticket,conversation:WhatsAppConversation}
     */
    protected function crmWorkspaceWithQuotation(string $quotationStatus, string $opportunityStatus): array
    {
        $customer = Customer::factory()->create(['name' => 'Deal Matrix Customer']);
        $conversation = WhatsAppConversation::query()->create([
            'customer_id' => $customer->id,
            'contact_name' => 'Deal Matrix Contact',
            'phone_number' => '628700000099',
            'channel' => 'whatsapp',
            'last_message' => 'Need deal status',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'phone' => '628700000099',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need deal status',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'name' => 'Deal Matrix Lead',
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Deal Matrix Opportunity',
            'status' => $opportunityStatus,
            'probability' => $opportunityStatus === 'won' ? 100 : ($opportunityStatus === 'lost' ? 0 : 40),
            'won_at' => $opportunityStatus === 'won' ? now() : null,
            'lost_at' => $opportunityStatus === 'lost' ? now() : null,
            'lost_reason' => $opportunityStatus === 'lost' ? $quotationStatus : null,
        ]);
        $quotation = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'conversation_id' => $conversation->id,
            'quote_number' => 'QTN-DEAL-MATRIX-'.strtoupper($quotationStatus),
            'status' => $quotationStatus,
            'amount' => 75000000,
        ]);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'ticket_number' => 'TCK-DEAL-MATRIX-'.strtoupper($quotationStatus),
            'subject' => 'Deal matrix ticket',
            'status' => 'open',
        ]);

        return compact('customer', 'lead', 'opportunity', 'quotation', 'ticket', 'conversation');
    }
}
