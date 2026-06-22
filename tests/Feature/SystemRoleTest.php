<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_system_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.system.users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_admin_can_access_system_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.system.users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_sales_cannot_access_system_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->get(route('admin.system.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_system_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.system.roles.index'))
            ->assertOk()
            ->assertSee('Roles &amp; Permissions', false);
    }

    public function test_create_role_succeeds(): void
    {
        $this->post(route('admin.system.roles.store'), [
            'name' => 'finance',
            'permissions' => ['customers.view', 'campaigns.view'],
        ])->assertRedirect(route('admin.system.roles.index'));

        $role = Role::findByName('finance');

        $this->assertTrue($role->hasPermissionTo('customers.view'));
        $this->assertTrue($role->hasPermissionTo('campaigns.view'));
    }

    public function test_update_role_permissions_succeeds(): void
    {
        $role = Role::create(['name' => 'ops', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view']);

        $this->put(route('admin.system.roles.update', $role), [
            'name' => 'ops',
            'permissions' => ['tickets.view', 'omnichannel.view'],
        ])->assertRedirect(route('admin.system.roles.index'));

        $role->refresh();

        $this->assertFalse($role->hasPermissionTo('customers.view'));
        $this->assertTrue($role->hasPermissionTo('tickets.view'));
        $this->assertTrue($role->hasPermissionTo('omnichannel.view'));
    }

    public function test_edit_role_displays_and_saves_non_standard_database_permission(): void
    {
        $role = Role::create(['name' => 'content_ops', 'guard_name' => 'web']);
        $permission = Permission::findOrCreate('whatsapp_templates.publish', 'web');

        $this->get(route('admin.system.roles.edit', $role))
            ->assertOk()
            ->assertSee('WhatsApp Templates')
            ->assertSee('whatsapp_templates.publish')
            ->assertSee('Akses Lainnya');

        $this->put(route('admin.system.roles.update', $role), [
            'name' => 'content_ops',
            'permissions' => [$permission->name],
        ])->assertRedirect(route('admin.system.roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_role_pages_group_whatsapp_permissions_under_whatsapp_marketing(): void
    {
        $role = Role::findByName('marketing');

        foreach ([
            route('admin.system.roles.create'),
            route('admin.system.roles.edit', $role),
            route('admin.system.roles.show', $role),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('WhatsApp Marketing')
                ->assertSee('WhatsApp Cloud API')
                ->assertSee('WhatsApp Templates')
                ->assertSee('WhatsApp Broadcast')
                ->assertSee('WhatsApp Reply Inbox');
        }

        $this->get(route('admin.system.roles.edit', $role))
            ->assertSee('WhatsApp Providers')
            ->assertSee('Pilih Semua WhatsApp Marketing');
    }

    public function test_super_admin_role_builder_marks_every_database_permission_as_active(): void
    {
        $role = Role::findByName('super_admin');
        $customPermission = Permission::findOrCreate('reports.export', 'web');
        $role->revokePermissionTo('whatsapp_templates.view');

        $response = $this->get(route('admin.system.roles.edit', $role));

        $response
            ->assertOk()
            ->assertSee('WhatsApp Marketing')
            ->assertSee('20 dari 20 akses dipilih')
            ->assertSee('WhatsApp Providers')
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('WhatsApp Templates')
            ->assertSee('WhatsApp Broadcast')
            ->assertSee('WhatsApp Reply Inbox');

        $this->assertSame(1, substr_count($response->getContent(), '>WhatsApp Marketing<'));

        foreach (Permission::query()->where('guard_name', 'web')->pluck('name') as $permission) {
            $this->assertMatchesRegularExpression(
                '/value="'.preg_quote($permission, '/').'"[^>]*checked[^>]*disabled/s',
                $response->getContent(),
                "Permission {$permission} tidak aktif pada Role Builder super_admin.",
            );
        }

        $this->put(route('admin.system.roles.update', $role), [
            'name' => 'super_admin',
            'permissions' => [],
        ])->assertRedirect(route('admin.system.roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo($customPermission));
        $this->assertTrue($role->fresh()->hasPermissionTo('whatsapp_templates.view'));
    }

    public function test_cannot_delete_super_admin_role(): void
    {
        $role = Role::findByName('super_admin');

        $this->delete(route('admin.system.roles.destroy', $role))
            ->assertRedirect(route('admin.system.roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
    }

    public function test_cannot_delete_role_that_is_still_used_by_user(): void
    {
        $role = Role::create(['name' => 'temporary_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->delete(route('admin.system.roles.destroy', $role))
            ->assertRedirect(route('admin.system.roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'temporary_admin']);
    }

    public function test_sidebar_system_is_visible_for_super_admin_and_admin(): void
    {
        foreach (['super_admin', 'admin'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('System')
                ->assertSee('Roles &amp; Permissions', false)
                ->assertSee('Users');
        }
    }

    public function test_sidebar_system_is_hidden_for_sales(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Roles &amp; Permissions', false)
            ->assertDontSee('System');
    }
}
