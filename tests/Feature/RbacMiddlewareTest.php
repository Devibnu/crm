<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_route(): void
    {
        auth()->logout();

        $this->get(route('admin.system.roles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_receives_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.system.roles.index'))
            ->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_access_route(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.view');

        $this->actingAs($user)
            ->get(route('admin.system.roles.index'))
            ->assertOk();
    }

    public function test_hardened_routes_have_expected_permission_middleware(): void
    {
        $expected = [
            'admin.marketing.whatsapp-cloud-api.index' => 'permission:whatsapp_cloud_api.view',
            'admin.marketing.whatsapp-templates.store' => 'permission:whatsapp_templates.create',
            'admin.marketing.whatsapp-broadcasts.update' => 'permission:whatsapp_broadcasts.update',
            'admin.marketing.whatsapp-replies.index' => 'permission:whatsapp_replies.view',
            'admin.system.whatsapp-providers.index' => 'permission:whatsapp_providers.view',
            'admin.system.roles.destroy' => 'permission:roles.delete',
            'admin.system.menus.reorder' => 'permission:menus.update',
            'admin.system.branding.update' => 'permission:branding.update',
            'admin.service.sla.store' => 'permission:sla.create',
            'admin.service.case-resolutions.update' => 'permission:cases.update',
            'admin.service.customer-satisfaction.destroy' => 'permission:csat.delete',
        ];

        foreach ($expected as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route {$routeName} is not registered.");
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
    }
}
