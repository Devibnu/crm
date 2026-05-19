<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.transactions'));

        $response
            ->assertOk()
            ->assertSee('Transactions');
    }

    public function test_transaction_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'title' => 'Deal Mesin Baja',
            'amount' => 12500000.50,
            'status' => 'pending',
            'closing_date' => '2026-05-30',
            'description' => 'Initial negotiation ongoing.',
        ];

        $response = $this->post(route('admin.customers.transactions.store', $customer), $payload);

        $response->assertRedirect(route('admin.customers.transactions'));

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'title' => 'Deal Mesin Baja',
            'status' => 'pending',
        ]);
    }

    public function test_transaction_edit_page_is_accessible(): void
    {
        $transaction = CustomerTransaction::factory()->create();

        $this->get(route('admin.customers.transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('Edit Transaction');
    }

    public function test_transaction_can_be_updated(): void
    {
        $transaction = CustomerTransaction::factory()->create([
            'title' => 'Old Deal',
            'status' => 'pending',
        ]);

        $newCustomer = Customer::factory()->create();

        $response = $this->put(route('admin.customers.transactions.update', $transaction), [
            'customer_id' => $newCustomer->id,
            'title' => 'Updated Deal',
            'amount' => 22000000,
            'status' => 'won',
            'closing_date' => '2026-06-12',
            'description' => 'Deal closed successfully.',
        ]);

        $response->assertRedirect(route('admin.customers.transactions'));

        $this->assertDatabaseHas('customer_transactions', [
            'id' => $transaction->id,
            'customer_id' => $newCustomer->id,
            'title' => 'Updated Deal',
            'status' => 'won',
        ]);
    }

    public function test_transaction_can_be_deleted(): void
    {
        $transaction = CustomerTransaction::factory()->create();

        $response = $this->delete(route('admin.customers.transactions.destroy', $transaction));

        $response->assertRedirect(route('admin.customers.transactions'));

        $this->assertDatabaseMissing('customer_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_transaction_search_works(): void
    {
        $customerA = Customer::factory()->create(['name' => 'Search Customer A']);

        CustomerTransaction::factory()->create([
            'customer_id' => $customerA->id,
            'title' => 'Unique Pipeline Deal',
        ]);

        CustomerTransaction::factory()->create([
            'title' => 'Unrelated Deal',
        ]);

        $this->get(route('admin.customers.transactions', ['q' => 'Unique Pipeline Deal']))
            ->assertOk()
            ->assertSee('Unique Pipeline Deal')
            ->assertDontSee('Unrelated Deal');

        $this->get(route('admin.customers.transactions', ['q' => 'Search Customer A']))
            ->assertOk()
            ->assertSee('Unique Pipeline Deal');
    }

    public function test_transaction_status_filter_works(): void
    {
        CustomerTransaction::factory()->create([
            'status' => 'won',
            'title' => 'Won Deal',
        ]);

        CustomerTransaction::factory()->create([
            'status' => 'lost',
            'title' => 'Lost Deal',
        ]);

        $this->get(route('admin.customers.transactions', ['status' => 'won']))
            ->assertOk()
            ->assertSee('Won Deal')
            ->assertDontSee('Lost Deal');
    }

    public function test_customer_show_page_displays_latest_transactions(): void
    {
        $customer = Customer::factory()->create();

        $oldest = CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'title' => 'Oldest Deal',
            'closing_date' => '2026-01-01',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            CustomerTransaction::factory()->create([
                'customer_id' => $customer->id,
                'title' => "Newest Deal {$i}",
                'closing_date' => now()->addDays($i)->toDateString(),
            ]);
        }

        $response = $this->get(route('admin.customers.show', $customer));

        $response
            ->assertOk()
            ->assertSee('Newest Deal 1')
            ->assertSee('Newest Deal 2')
            ->assertSee('Newest Deal 3')
            ->assertSee('Newest Deal 4')
            ->assertSee('Newest Deal 5')
            ->assertDontSee($oldest->title);
    }
}
