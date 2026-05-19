<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.index'));

        $response
            ->assertOk()
            ->assertSee('Customer List');
    }

    public function test_customer_can_be_created(): void
    {
        $payload = [
            'name' => 'PT Krakatau Test',
            'company_name' => 'Krakatau Company',
            'email' => 'customer-create@example.com',
            'phone' => '081234567890',
            'whatsapp' => '6281234567890',
            'source' => 'Website',
            'status' => 'active',
            'owner_name' => 'Owner One',
            'notes' => 'Created from feature test.',
        ];

        $response = $this->post(route('admin.customers.store'), $payload);

        $response->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'PT Krakatau Test',
            'email' => 'customer-create@example.com',
            'status' => 'active',
        ]);
    }

    public function test_customer_show_and_edit_pages_are_accessible(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->name);

        $this->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee('Edit Customer');
    }

    public function test_customer_can_be_updated(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Before Update',
            'email' => 'before-update@example.com',
            'status' => 'new',
        ]);

        $response = $this->put(route('admin.customers.update', $customer), [
            'name' => 'After Update',
            'company_name' => 'Updated Co',
            'email' => 'after-update@example.com',
            'phone' => '0899999999',
            'whatsapp' => '6289999999',
            'source' => 'Referral',
            'status' => 'inactive',
            'owner_name' => 'Owner Two',
            'notes' => 'Updated from feature test.',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'After Update',
            'email' => 'after-update@example.com',
            'status' => 'inactive',
        ]);
    }

    public function test_customer_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->delete(route('admin.customers.destroy', $customer));

        $response->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_customer_search_works_for_name_email_phone_and_company(): void
    {
        $byName = Customer::factory()->create([
            'name' => 'Alpha Search Name',
            'email' => 'alpha-search@example.com',
            'phone' => '081111111111',
            'company_name' => 'Alpha Corp',
        ]);

        $byEmail = Customer::factory()->create([
            'name' => 'Beta Name',
            'email' => 'search-email@example.com',
            'phone' => '082222222222',
            'company_name' => 'Beta Corp',
        ]);

        $byPhone = Customer::factory()->create([
            'name' => 'Gamma Name',
            'email' => 'gamma@example.com',
            'phone' => '083333333333',
            'company_name' => 'Gamma Corp',
        ]);

        $byCompany = Customer::factory()->create([
            'name' => 'Delta Name',
            'email' => 'delta@example.com',
            'phone' => '084444444444',
            'company_name' => 'Unique Company Search',
        ]);

        $this->get(route('admin.customers.index', ['q' => 'Alpha Search Name']))
            ->assertOk()
            ->assertSee($byName->name)
            ->assertDontSee($byEmail->name);

        $this->get(route('admin.customers.index', ['q' => 'search-email@example.com']))
            ->assertOk()
            ->assertSee($byEmail->name)
            ->assertDontSee($byPhone->name);

        $this->get(route('admin.customers.index', ['q' => '083333333333']))
            ->assertOk()
            ->assertSee($byPhone->name)
            ->assertDontSee($byCompany->name);

        $this->get(route('admin.customers.index', ['q' => 'Unique Company Search']))
            ->assertOk()
            ->assertSee($byCompany->name)
            ->assertDontSee($byName->name);
    }
}
