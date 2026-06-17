<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerBehavior;
use App\Models\CustomerInteraction;
use App\Models\CustomerPreference;
use App\Models\CustomerTransaction;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_without_customer_id_shows_selector_and_customer_list(): void
    {
        $customer = Customer::factory()->create(['name' => 'Selector Customer']);

        $this->get(route('admin.customers.profile'))
            ->assertOk()
            ->assertSee('Customer Selector')
            ->assertSee('Selector Customer')
            ->assertSee('Lihat Profil')
            ->assertSee(route('admin.customers.profile', ['customer_id' => $customer->id]), false);
    }

    public function test_profile_customer_search_filters_customer_list(): void
    {
        Customer::factory()->create(['name' => 'Needle Customer']);
        Customer::factory()->create(['name' => 'Hidden Customer']);

        $this->get(route('admin.customers.profile', ['q' => 'Needle']))
            ->assertOk()
            ->assertSee('Needle Customer')
            ->assertDontSee('Hidden Customer');
    }

    public function test_profile_with_customer_id_shows_customer_data_and_summary_cards(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Profile Target Customer',
            'email' => 'profile-target@example.com',
            'phone' => '021555000',
            'whatsapp' => '628123456789',
            'company_name' => 'Profile Company',
            'status' => 'active',
        ]);

        CustomerInteraction::factory()->create(['customer_id' => $customer->id, 'subject' => 'Latest Customer Call']);
        CustomerTransaction::factory()->create(['customer_id' => $customer->id, 'title' => 'Latest Customer Deal']);
        CustomerPreference::factory()->create(['customer_id' => $customer->id, 'preferred_channel' => 'whatsapp']);
        CustomerBehavior::factory()->create(['customer_id' => $customer->id, 'lifecycle_stage' => 'active']);
        Opportunity::factory()->create(['customer_id' => $customer->id, 'title' => 'Profile Opportunity']);
        Quotation::factory()->create(['customer_id' => $customer->id, 'title' => 'Profile Quotation']);

        $this->get(route('admin.customers.profile', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('Profile Target Customer')
            ->assertSee('profile-target@example.com')
            ->assertSee('021555000')
            ->assertSee('628123456789')
            ->assertSee('Profile Company')
            ->assertSee('Total Interactions')
            ->assertSee('Total Transactions')
            ->assertSee('Total Preferences')
            ->assertSee('Behavior Records')
            ->assertSee('Total Opportunities')
            ->assertSee('Total Quotations')
            ->assertSee('Latest Customer Call')
            ->assertSee('Latest Customer Deal')
            ->assertSee('Profile Opportunity')
            ->assertSee('Profile Quotation');
    }

    public function test_empty_state_appears_for_customer_without_related_data(): void
    {
        $customer = Customer::factory()->create(['name' => 'Empty Profile Customer']);

        $this->get(route('admin.customers.profile', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('Empty Profile Customer')
            ->assertSee('Belum ada interactions.')
            ->assertSee('Belum ada transaction')
            ->assertSee('Belum ada preference')
            ->assertSee('Belum ada behavior');
    }

    public function test_profile_action_links_are_visible(): void
    {
        $customer = Customer::factory()->create(['name' => 'Action Link Customer']);

        $this->get(route('admin.customers.profile', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee(route('admin.customers.edit', $customer), false)
            ->assertSee(route('admin.customers.interactions.create', $customer), false)
            ->assertSee(route('admin.customers.transactions.create', $customer), false)
            ->assertSee(route('admin.customers.preferences.create', $customer), false)
            ->assertSee(route('admin.customers.behavior.create', $customer), false);
    }
}
