<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_is_redirected_to_first_allowed_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.dashboard.sales'));
    }

    public function test_sales_only_sees_assigned_dashboard_menu_items(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Enablement')
            ->assertSee('Customer Profile 360')
            ->assertDontSee('CRM Overview')
            ->assertDontSee('Service Management')
            ->assertDontSee('Marketing Automation');
    }

    public function test_sales_cannot_open_unassigned_dashboard_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)->get(route('admin.dashboard.crm'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard.service'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard.marketing'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard.sales'))->assertOk();
        $this->actingAs($user)->get(route('admin.dashboard.customer'))->assertOk();
    }
}
