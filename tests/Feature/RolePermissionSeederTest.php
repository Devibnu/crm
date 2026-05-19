<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RbacPermissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_seeder_creates_roles_permissions_and_assigns_first_user(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $firstUser = User::query()->orderBy('id')->first();

        foreach (['super_admin', 'admin', 'manager', 'sales', 'marketing', 'support'] as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }

        $this->assertSame(count(RbacPermissions::all()), Permission::count());
        $this->assertTrue($firstUser?->fresh()->hasRole('super_admin'));
        $this->assertTrue(Role::findByName('super_admin')->hasAllPermissions(RbacPermissions::all()));
        $this->assertFalse(Role::findByName('admin')->hasPermissionTo('users.delete'));
    }

    public function test_sales_can_access_leads_but_not_marketing_automations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('sales');

        $this->actingAs($user)
            ->get(route('admin.sales.leads'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.marketing.automations.index'))
            ->assertForbidden();
    }

    public function test_marketing_can_access_campaigns(): void
    {
        $user = User::factory()->create();
        $user->assignRole('marketing');

        $this->actingAs($user)
            ->get(route('admin.marketing.campaigns.index'))
            ->assertOk();
    }

    public function test_support_can_access_tickets_and_omnichannel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('support');

        $this->actingAs($user)
            ->get(route('admin.service.tickets.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.service.omnichannel.index'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_protected_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.sales.leads'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_all_checked_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        foreach ([
            route('admin.sales.leads'),
            route('admin.marketing.automations.index'),
            route('admin.marketing.campaigns.index'),
            route('admin.service.tickets.index'),
            route('admin.service.omnichannel.index'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_role_permission_page_can_be_opened_by_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.system.roles.index'))
            ->assertOk()
            ->assertSee('Roles & Permissions');
    }
}
