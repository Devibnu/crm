<?php

namespace Tests\Feature;

use App\Models\Menu;
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
            ->assertDontSee('Sales Activity Tracking');
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
}
