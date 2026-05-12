<?php

namespace Tests\Feature;

use App\Models\CampaignExecution;
use App\Models\CustomerBehavior;
use App\Models\CustomerInteraction;
use App\Models\CustomerPreference;
use App\Models\CustomerSatisfaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Lead;
use App\Models\KnowledgeBase;
use App\Models\LandingPage;
use App\Models\LeadScoringRule;
use App\Models\MarketingAutomation;
use App\Models\MarketingCampaign;
use App\Models\OmnichannelMessage;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\SalesActivity;
use App\Models\SlaPolicy;
use App\Models\SocialMediaEngagement;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_overview_renders_real_data(): void
    {
        $user = User::factory()->create();

        Customer::factory()->create(['name' => 'PT Nusantara Jaya', 'status' => 'active']);
        Lead::factory()->create(['name' => 'Lead Mega Retail', 'status' => 'qualified']);
        Opportunity::factory()->create([
            'title' => 'Opportunity Infrastruktur',
            'estimated_value' => 125000000,
            'status' => 'won',
        ]);
        Ticket::factory()->create([
            'ticket_number' => 'TCK-TEST-1001',
            'subject' => 'Gangguan Integrasi API',
            'status' => 'open',
        ]);
        MarketingCampaign::factory()->create([
            'name' => 'Campaign Ramadan Enterprise',
            'status' => 'running',
        ]);
        CampaignExecution::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response
            ->assertOk()
            ->assertSee('CRM Overview')
            ->assertSee('Total Customers')
            ->assertSee('Total Leads')
            ->assertSee('Pipeline Value')
            ->assertSee('Open Tickets')
            ->assertSee('Running Campaigns')
            ->assertSee('Win Rate')
            ->assertSee('PT Nusantara Jaya')
            ->assertSee('Lead Mega Retail')
            ->assertSee('Opportunity Infrastruktur')
            ->assertSee('Gangguan Integrasi API')
            ->assertSee('Campaign Ramadan Enterprise')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('ErrorException')
            ->assertDontSee('syntax error');
    }

    public function test_service_management_dashboard_renders_real_data(): void
    {
        $user = User::factory()->create();

        Ticket::factory()->create([
            'ticket_number' => 'TCK-SVC-2026-0001',
            'subject' => 'Service Dashboard Ticket',
            'status' => 'open',
            'priority' => 'high',
        ]);

        OmnichannelMessage::factory()->create([
            'channel' => 'whatsapp',
            'sender_name' => 'Service Sender',
            'subject' => 'Service Omnichannel Message',
            'status' => 'unread',
        ]);

        CustomerSatisfaction::factory()->create([
            'rating' => 5,
            'sentiment' => 'positive',
            'feedback' => 'Service dashboard feedback',
        ]);

        KnowledgeBase::factory()->create([
            'title' => 'Service Dashboard Knowledge',
            'is_published' => true,
        ]);

        SlaPolicy::factory()->create([
            'name' => 'Service Dashboard SLA',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard.service'));

        $response
            ->assertOk()
            ->assertSee('Service Management Dashboard')
            ->assertSee('Total Tickets')
            ->assertSee('Open Tickets')
            ->assertSee('Unread Messages')
            ->assertSee('Average CSAT')
            ->assertSee('Knowledge Articles')
            ->assertSee('Active SLA')
            ->assertSee('Service Dashboard Ticket')
            ->assertSee('Service Omnichannel Message')
            ->assertSee('Service Dashboard Knowledge')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('ErrorException')
            ->assertDontSee('syntax error');
    }

    public function test_sales_enablement_dashboard_renders_real_data(): void
    {
        $user = User::factory()->create();

        Lead::factory()->create([
            'name' => 'Sales Dashboard Lead',
            'status' => 'qualified',
        ]);

        Opportunity::factory()->create([
            'title' => 'Sales Dashboard Opportunity',
            'estimated_value' => 95000000,
            'probability' => 70,
            'status' => 'open',
        ]);

        SalesActivity::factory()->create([
            'type' => 'meeting',
            'subject' => 'Sales Dashboard Activity',
            'related_type' => 'lead',
        ]);

        Quotation::factory()->create([
            'quote_number' => 'QTN-2026-7777',
            'title' => 'Sales Dashboard Quotation',
            'status' => 'accepted',
            'amount' => 88000000,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard.sales'));

        $response
            ->assertOk()
            ->assertSee('Sales Enablement Dashboard')
            ->assertSee('Total Leads')
            ->assertSee('Total Opportunities')
            ->assertSee('Pipeline Value')
            ->assertSee('Win Rate')
            ->assertSee('Sales Activities')
            ->assertSee('Quotation Value')
            ->assertSee('Sales Dashboard Lead')
            ->assertSee('Sales Dashboard Opportunity')
            ->assertSee('Sales Dashboard Activity')
            ->assertSee('Sales Dashboard Quotation')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('ErrorException')
            ->assertDontSee('syntax error');
    }

    public function test_marketing_automation_dashboard_renders_real_data(): void
    {
        $user = User::factory()->create();

        $campaign = MarketingCampaign::factory()->create([
            'name' => 'Marketing Dashboard Campaign',
            'status' => 'running',
            'type' => 'email',
        ]);

        CampaignExecution::factory()->create([
            'marketing_campaign_id' => $campaign->id,
            'execution_name' => 'Marketing Dashboard Execution',
            'status' => 'completed',
            'channel' => 'email',
            'sent_count' => 3200,
        ]);

        LandingPage::factory()->create([
            'title' => 'Marketing Dashboard Landing',
            'status' => 'published',
            'views_count' => 12000,
            'submissions_count' => 875,
        ]);

        SocialMediaEngagement::factory()->create([
            'post_title' => 'Marketing Dashboard Social Post',
            'platform' => 'linkedin',
            'status' => 'published',
            'impressions_count' => 98000,
            'engagement_rate' => 6.45,
        ]);

        MarketingAutomation::factory()->create([
            'name' => 'Marketing Dashboard Automation',
            'status' => 'active',
        ]);

        LeadScoringRule::factory()->create([
            'name' => 'Marketing Dashboard Scoring',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard.marketing'));

        $response
            ->assertOk()
            ->assertSee('Marketing Automation Dashboard')
            ->assertSee('Total Campaigns')
            ->assertSee('Total Executions')
            ->assertSee('Total Sent')
            ->assertSee('Landing Pages')
            ->assertSee('Social Posts')
            ->assertSee('Automations')
            ->assertSee('Campaign Status Overview')
            ->assertSee('Execution Performance Overview')
            ->assertSee('Landing Page Performance')
            ->assertSee('Social Engagement Overview')
            ->assertSee('Automation Overview')
            ->assertSee('Marketing Dashboard Campaign')
            ->assertSee('Marketing Dashboard Execution')
            ->assertSee('Marketing Dashboard Landing')
            ->assertSee('Marketing Dashboard Social Post')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('ErrorException')
            ->assertDontSee('syntax error');
    }

    public function test_customer_profile_dashboard_renders_real_data(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create([
            'name' => 'Customer Profile Dashboard Customer',
            'status' => 'active',
        ]);

        CustomerInteraction::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'meeting',
            'subject' => 'Customer Profile Interaction',
        ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'title' => 'Customer Profile Transaction',
            'status' => 'won',
            'amount' => 75000000,
        ]);

        CustomerPreference::factory()->create([
            'customer_id' => $customer->id,
            'preferred_channel' => 'email',
            'product_interest' => 'CRM Enterprise',
            'communication_consent' => true,
        ]);

        CustomerBehavior::factory()->create([
            'customer_id' => $customer->id,
            'lifecycle_stage' => 'active',
            'engagement_score' => 82,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard.customer'));

        $response
            ->assertOk()
            ->assertSee('Customer Profile 360 Dashboard')
            ->assertSee('Total Customers')
            ->assertSee('Total Interactions')
            ->assertSee('Total Preferences')
            ->assertSee('Total Transactions')
            ->assertSee('Won Transaction Value')
            ->assertSee('Average Engagement Score')
            ->assertSee('Recent Customers')
            ->assertSee('Customer Profile Dashboard Customer')
            ->assertSee('Customer Profile Interaction')
            ->assertSee('Customer Profile Transaction')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('ErrorException')
            ->assertDontSee('syntax error');
    }
}
