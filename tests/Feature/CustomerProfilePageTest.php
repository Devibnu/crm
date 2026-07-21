<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_profile_without_customer_id_redirects_to_customer_list(): void
    {
        $this->get(route('admin.customers.profile'))
            ->assertRedirect(route('admin.customers.index'));
    }

    public function test_legacy_profile_with_customer_id_redirects_to_customer_show(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.profile', ['customer_id' => $customer->id]))
            ->assertRedirect(route('admin.customers.show', $customer));
    }

    public function test_legacy_profile_with_invalid_customer_id_returns_not_found(): void
    {
        $this->get(route('admin.customers.profile', ['customer_id' => 999999]))
            ->assertNotFound();
    }

    public function test_customer_profile_menu_is_not_visible_in_sidebar(): void
    {
        $this->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Customer Profile 360')
            ->assertSee('Customer List')
            ->assertSee('Interaction History')
            ->assertSee('Transactions')
            ->assertSee('Preferences')
            ->assertSee('Behavior')
            ->assertDontSee('>Customer Profile</span>', false)
            ->assertDontSee(route('admin.customers.profile'), false);
    }

    public function test_stale_database_customer_profile_menu_is_filtered_from_sidebar(): void
    {
        Menu::query()->create([
            'section' => 'customer-profile-360',
            'title' => 'Customer List',
            'route' => 'admin.customers.index',
            'icon' => 'user',
            'permission_name' => 'customers.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Menu::query()->create([
            'section' => 'customer-profile-360',
            'title' => 'Customer Profile',
            'route' => 'admin.customers.profile',
            'icon' => 'user',
            'permission_name' => 'customers.view',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Customer List')
            ->assertDontSee('>Customer Profile</span>', false)
            ->assertDontSee(route('admin.customers.profile'), false);
    }

    public function test_customer_list_stays_active_on_customer_show(): void
    {
        $customer = Customer::factory()->create();
        $activeCustomerListNavigation = 'href="'.route('admin.customers.index').'" class="nav-link parent compact active"';

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($activeCustomerListNavigation, false);
    }

    public function test_customer_list_view_360_uses_canonical_customer_show_route(): void
    {
        $customer = Customer::factory()->create(['name' => 'Canonical Customer']);

        $this->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Canonical Customer')
            ->assertSee('href="'.route('admin.customers.show', $customer).'"', false)
            ->assertDontSee(route('admin.customers.profile', ['customer_id' => $customer->id]), false);
    }

    public function test_customer_list_hides_unauthorized_actions(): void
    {
        $role = Role::create(['name' => 'customer_read_only', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        Customer::factory()->create(['name' => 'Read Only Customer']);

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Read Only Customer')
            ->assertSee('View 360')
            ->assertSee('View Transactions')
            ->assertDontSee('Add Customer')
            ->assertDontSee('Edit')
            ->assertDontSee('Add Interaction')
            ->assertDontSee('Delete');
    }
}
