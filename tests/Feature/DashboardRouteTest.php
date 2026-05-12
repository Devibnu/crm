<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\Opportunity;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_overview_dashboard_uses_real_data(): void
    {
        Customer::factory()->create(['name' => 'Dashboard Customer']);
        Lead::factory()->create(['name' => 'Dashboard Lead']);
        Ticket::factory()->create(['subject' => 'Dashboard Ticket']);
        MarketingCampaign::factory()->create(['name' => 'Dashboard Campaign', 'status' => 'running']);

        $this->get(route('admin.dashboard.crm'))
            ->assertOk()
            ->assertSee('CRM Overview')
            ->assertSee('Dashboard Customer')
            ->assertSee('Dashboard Lead')
            ->assertSee('Dashboard Ticket');
    }

    public function test_service_management_dashboard_uses_real_data(): void
    {
        Ticket::factory()->create(['ticket_number' => 'TCK-DASH-001', 'subject' => 'Service Dashboard Ticket', 'status' => 'open']);

        $this->get(route('admin.dashboard.service'))
            ->assertOk()
            ->assertSee('Service Management')
            ->assertSee('Service Dashboard Ticket')
            ->assertSee('Open');
    }

    public function test_sales_enablement_dashboard_uses_real_data(): void
    {
        Lead::factory()->create(['status' => 'qualified']);
        Opportunity::factory()->create(['title' => 'Sales Dashboard Opportunity', 'estimated_value' => 5000000, 'status' => 'open']);

        $this->get(route('admin.dashboard.sales'))
            ->assertOk()
            ->assertSee('Sales Enablement')
            ->assertSee('Sales Dashboard Opportunity')
            ->assertSee('Rp 5.000.000');
    }

    public function test_marketing_automation_dashboard_uses_real_data(): void
    {
        MarketingCampaign::factory()->create(['name' => 'Marketing Dashboard Campaign', 'actual_leads' => 12, 'status' => 'running']);

        $this->get(route('admin.dashboard.marketing'))
            ->assertOk()
            ->assertSee('Marketing Automation')
            ->assertSee('Marketing Dashboard Campaign')
            ->assertSee('12');
    }

    public function test_customer_profile_dashboard_uses_real_data(): void
    {
        $customer = Customer::factory()->create(['name' => 'Customer Profile Dashboard', 'status' => 'active']);
        CustomerInteraction::factory()->create(['customer_id' => $customer->id, 'subject' => 'Profile Interaction']);

        $this->get(route('admin.dashboard.customer'))
            ->assertOk()
            ->assertSee('Customer Profile 360')
            ->assertSee('Customer Profile Dashboard')
            ->assertSee('Profile Interaction');
    }
}
