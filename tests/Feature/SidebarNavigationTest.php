<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_falls_back_to_hardcoded_menu_when_database_menus_are_empty(): void
    {
        Menu::query()->delete();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Activity Tracking')
            ->assertSee('Quotation &amp; Deal', false)
            ->assertSee('Project Management')
            ->assertSee('Project Dashboard')
            ->assertSee('Projects')
            ->assertSee(route('admin.projects.dashboard'), false)
            ->assertSee(route('admin.projects.index'), false)
            ->assertSee('WHATSAPP MARKETING')
            ->assertSee('WhatsApp Templates')
            ->assertSee('SERVICE MANAGEMENT')
            ->assertSee('Omnichannel Inbox');
    }

    public function test_sidebar_reads_database_menus_when_available(): void
    {
        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'DB Sales Leads',
            'route' => 'admin.sales.leads',
            'icon' => 'lead',
            'permission_name' => 'leads.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('DB Sales Leads')
            ->assertSee(route('admin.sales.leads'), false)
            ->assertSee('Project Management')
            ->assertSee('Project Dashboard')
            ->assertSee(route('admin.projects.dashboard'), false)
            ->assertSee(route('admin.projects.index'), false)
            ->assertDontSee('Sales Activity Tracking');
    }

    public function test_database_sidebar_keeps_project_management_when_project_menu_records_are_missing(): void
    {
        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'DB Sales Leads',
            'route' => 'admin.sales.leads',
            'icon' => 'lead',
            'permission_name' => 'leads.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Project Management')
            ->assertSee('Project Dashboard')
            ->assertSee('Projects')
            ->assertSee(route('admin.projects.dashboard'), false)
            ->assertSee(route('admin.projects.index'), false)
            ->assertDontSee(route('admin.sales.projects.index'), false);
    }

    public function test_database_sidebar_can_read_project_management_menu_records(): void
    {
        Menu::query()->create([
            'section' => 'project-management',
            'title' => 'Project Dashboard',
            'route' => 'admin.projects.dashboard',
            'icon' => 'dashboard',
            'permission_name' => 'projects.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        Menu::query()->create([
            'section' => 'project-management',
            'title' => 'Projects',
            'route' => 'admin.projects.index',
            'icon' => 'pipeline',
            'permission_name' => 'projects.view',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Project Management')
            ->assertSee('Project Dashboard')
            ->assertSee('Projects')
            ->assertSee(route('admin.projects.dashboard'), false)
            ->assertSee(route('admin.projects.index'), false)
            ->assertDontSee(route('admin.sales.projects.index'), false);
    }

    public function test_database_sidebar_filters_menu_by_permission_name(): void
    {
        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'Activities From DB',
            'route' => 'admin.sales.activities.index',
            'icon' => 'activity',
            'permission_name' => 'activities.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'Deals From DB',
            'route' => 'admin.sales.deals.index',
            'icon' => 'deal',
            'permission_name' => 'quotations.view',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'activity_db_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['activities.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Activities From DB')
            ->assertDontSee('Deals From DB');
    }

    public function test_super_admin_sees_all_active_database_menus(): void
    {
        $role = Role::findByName('super_admin');
        $role->revokePermissionTo('quotations.view');

        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'Protected Deal Menu',
            'route' => 'admin.sales.deals.index',
            'icon' => 'deal',
            'permission_name' => 'quotations.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        Menu::query()->create([
            'section' => 'sales-enablement',
            'title' => 'Inactive Deal Menu',
            'route' => 'admin.sales.deals.index',
            'icon' => 'deal',
            'permission_name' => 'quotations.view',
            'sort_order' => 20,
            'is_active' => false,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Protected Deal Menu')
            ->assertDontSee('Inactive Deal Menu');
    }

    public function test_super_admin_sees_every_whatsapp_sidebar_item(): void
    {
        $superAdmin = Role::findByName('super_admin');

        foreach ([
            'whatsapp_providers.view',
            'whatsapp_cloud_api.view',
            'whatsapp_templates.view',
            'whatsapp_broadcasts.view',
            'whatsapp_replies.view',
        ] as $permission) {
            $superAdmin->revokePermissionTo($permission);
        }

        $this->assertTrue(auth()->user()->can('whatsapp_providers.view'));
        $this->assertTrue(auth()->user()->can('whatsapp_replies.view'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('WHATSAPP MARKETING')
            ->assertSee('WhatsApp Providers')
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('WhatsApp Templates')
            ->assertSee('WhatsApp Broadcast')
            ->assertSee('WhatsApp Reply Inbox')
            ->assertSee('SERVICE MANAGEMENT')
            ->assertSee('Omnichannel Inbox')
            ->assertSee(route('admin.system.whatsapp-providers.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-cloud-api.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-templates.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-broadcasts.index'), false)
            ->assertSee(route('admin.marketing.whatsapp-replies.index'), false)
            ->assertSee(route('admin.service.omnichannel.index'), false);
    }

    public function test_role_with_whatsapp_view_permissions_sees_section_and_all_items(): void
    {
        $role = Role::create(['name' => 'whatsapp_operator', 'guard_name' => 'web']);
        $role->syncPermissions([
            'whatsapp_providers.view',
            'whatsapp_cloud_api.view',
            'whatsapp_templates.view',
            'whatsapp_broadcasts.view',
            'whatsapp_replies.view',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('WHATSAPP MARKETING')
            ->assertSee('WhatsApp Providers')
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('WhatsApp Templates')
            ->assertSee('WhatsApp Broadcast')
            ->assertSee('WhatsApp Reply Inbox');
    }

    public function test_role_without_whatsapp_permissions_does_not_see_section_or_items(): void
    {
        $role = Role::create(['name' => 'non_whatsapp_user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('WHATSAPP MARKETING')
            ->assertDontSee('WhatsApp Providers')
            ->assertDontSee('WhatsApp Cloud API')
            ->assertDontSee('WhatsApp Templates')
            ->assertDontSee('WhatsApp Broadcast')
            ->assertDontSee('WhatsApp Reply Inbox');
    }

    public function test_sales_sidebar_includes_activity_and_quotation_modules_for_authorized_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Activity Tracking')
            ->assertSee('Quotation &amp; Deal', false)
            ->assertSee(route('admin.sales.activities.index'), false)
            ->assertSee(route('admin.sales.deals.index'), false);
    }

    public function test_sales_modules_follow_their_own_view_permissions(): void
    {
        $role = Role::create(['name' => 'activity_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['activities.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Activity Tracking')
            ->assertDontSee('Quotation &amp; Deal', false);
    }

    public function test_system_sidebar_uses_permissions_instead_of_hard_coded_roles(): void
    {
        $role = Role::create(['name' => 'role_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['roles.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('System')
            ->assertSee('Roles &amp; Permissions', false)
            ->assertDontSee('Menu Management')
            ->assertDontSee('Branding');
    }

    public function test_ticket_management_sidebar_stays_active_across_ticket_routes(): void
    {
        $ticket = Ticket::factory()->create();
        $activeTicketNavigation = 'href="'.route('admin.service.tickets.index').'" class="nav-link parent compact active"';
        $activeSlaNavigation = 'href="'.route('admin.service.sla.index').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.service.tickets.index'),
            route('admin.service.tickets.create'),
            route('admin.service.tickets.show', $ticket),
            route('admin.service.tickets.edit', $ticket),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeTicketNavigation, false)
                ->assertDontSee($activeSlaNavigation, false);
        }
    }

    public function test_sla_management_sidebar_stays_active_across_sla_routes(): void
    {
        $policy = SlaPolicy::factory()->create();
        $activeTicketNavigation = 'href="'.route('admin.service.tickets.index').'" class="nav-link parent compact active"';
        $activeSlaNavigation = 'href="'.route('admin.service.sla.index').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.service.sla.index'),
            route('admin.service.sla.create'),
            route('admin.service.sla.show', $policy),
            route('admin.service.sla.edit', $policy),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeSlaNavigation, false)
                ->assertDontSee($activeTicketNavigation, false);
        }
    }

    public function test_sidebar_resource_active_pattern_also_works_without_database_menus(): void
    {
        Menu::query()->delete();
        $ticket = Ticket::factory()->create();

        $this->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('href="'.route('admin.service.tickets.index').'" class="nav-link parent compact active"', false)
            ->assertDontSee('href="'.route('admin.service.sla.index').'" class="nav-link parent compact active"', false);
    }

    public function test_sidebar_permission_visibility_is_preserved_with_resource_active_patterns(): void
    {
        $role = Role::create(['name' => 'ticket_sidebar_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['tickets.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.service.tickets.index'))
            ->assertOk()
            ->assertSee('Ticket Management')
            ->assertSee('href="'.route('admin.service.tickets.index').'" class="nav-link parent compact active"', false)
            ->assertDontSee('SLA Management')
            ->assertDontSee(route('admin.service.sla.index'), false);
    }
}
