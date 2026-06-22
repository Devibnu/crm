<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

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
}
