<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerTransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.transactions'));

        $response
            ->assertOk()
            ->assertSee('Transactions')
            ->assertSee('CUSTOMER PROFILE 360')
            ->assertSee('Total Transactions')
            ->assertSee('Won')
            ->assertSee('Pending')
            ->assertSee('Visible Value')
            ->assertSee('Search transactions...')
            ->assertSee('All Status')
            ->assertSee('New Transaction')
            ->assertDontSee('Riwayat transaksi, deal, quotation, purchase, dan revenue customer.');
    }

    public function test_customer_scoped_transaction_create_page_shows_locked_customer_context(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'PT Transaction Locked',
            'company_name' => 'Krakatau Revenue',
            'email' => 'finance@krakatau.test',
        ]);

        $response = $this->get(route('admin.customers.transactions.create', $customer));

        $response
            ->assertOk()
            ->assertSee('CUSTOMER TRANSACTIONS')
            ->assertSee('New Transaction')
            ->assertSee('PT Transaction Locked')
            ->assertSee('Krakatau Revenue')
            ->assertSee('finance@krakatau.test')
            ->assertSee('Customer Context')
            ->assertSee('Transaction Details')
            ->assertSee('Financial Details')
            ->assertSee('Notes')
            ->assertSee('name="customer_id"', false)
            ->assertSee('value="'.$customer->id.'"', false)
            ->assertDontSee('<select name="customer_id"', false)
            ->assertDontSee('Tambahkan transaksi customer untuk memantau pipeline deal dan revenue.');
    }

    public function test_transaction_index_uses_customer_selector_modal_for_create_flow(): void
    {
        $customerA = Customer::factory()->create([
            'name' => 'Transaction Selector A',
            'company_name' => 'Alpha Revenue',
            'email' => 'transaction-a@example.test',
        ]);
        $customerB = Customer::factory()->create([
            'name' => 'Transaction Selector B',
            'company_name' => 'Beta Revenue',
            'phone' => '0822222222',
        ]);

        $this->get(route('admin.customers.transactions'))
            ->assertOk()
            ->assertSee('data-customer-selector-trigger="newTransaction"', false)
            ->assertSee('data-customer-selector-modal="newTransaction"', false)
            ->assertSee('Select a customer before creating a transaction record.')
            ->assertSee('data-customer-selector-continue disabled', false)
            ->assertSee('Transaction Selector A')
            ->assertSee('Transaction Selector B')
            ->assertSee('data-url="'.route('admin.customers.transactions.create', ['customer' => $customerA]).'"', false)
            ->assertSee('data-url="'.route('admin.customers.transactions.create', ['customer' => $customerB]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.transactions.create', ['customer' => $customerA]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.transactions.create', ['customer' => $customerB]).'"', false);
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

    public function test_customer_scoped_transaction_cannot_be_created_for_different_customer(): void
    {
        $routeCustomer = Customer::factory()->create();
        $submittedCustomer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.transactions.create', $routeCustomer))
            ->post(route('admin.customers.transactions.store', $routeCustomer), [
                'customer_id' => $submittedCustomer->id,
                'title' => 'Mismatched Transaction',
                'amount' => 7500000,
                'status' => 'pending',
            ]);

        $response
            ->assertRedirect(route('admin.customers.transactions.create', $routeCustomer))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseMissing('customer_transactions', [
            'customer_id' => $submittedCustomer->id,
            'title' => 'Mismatched Transaction',
        ]);
    }

    public function test_transaction_create_validation_errors_preserve_old_input(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.transactions.create', $customer))
            ->post(route('admin.customers.transactions.store', $customer), [
                'customer_id' => $customer->id,
                'title' => '',
                'amount' => 1250000,
                'status' => 'won',
                'description' => 'Preserved transaction note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.transactions.create', $customer))
            ->assertSessionHasErrors('title')
            ->assertSessionHasInput('amount', 1250000)
            ->assertSessionHasInput('description', 'Preserved transaction note');
    }

    public function test_transaction_edit_page_is_accessible(): void
    {
        $customer = Customer::factory()->create(['name' => 'Editable Transaction Customer']);
        $transaction = CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'title' => 'Editable Deal',
            'status' => 'won',
        ]);

        $this->get(route('admin.customers.transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('CUSTOMER TRANSACTIONS')
            ->assertSee('Edit Transaction')
            ->assertSee('Editable Transaction Customer')
            ->assertSee('Won')
            ->assertSee('Customer Context')
            ->assertSee('Transaction Details')
            ->assertSee('Financial Details')
            ->assertSee('Notes')
            ->assertSee('<select name="customer_id" required>', false)
            ->assertDontSee('Perbarui detail transaksi agar data deal customer tetap akurat.');
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

    public function test_transaction_update_validation_errors_preserve_old_input(): void
    {
        $transaction = CustomerTransaction::factory()->create();

        $response = $this->from(route('admin.customers.transactions.edit', $transaction))
            ->put(route('admin.customers.transactions.update', $transaction), [
                'customer_id' => $transaction->customer_id,
                'title' => '',
                'amount' => 9900000,
                'status' => 'lost',
                'description' => 'Preserved edit transaction note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.transactions.edit', $transaction))
            ->assertSessionHasErrors('title')
            ->assertSessionHasInput('amount', 9900000)
            ->assertSessionHasInput('description', 'Preserved edit transaction note');
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

    public function test_transaction_index_pagination_preserves_search_and_status_query(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            CustomerTransaction::factory()->create([
                'status' => 'won',
                'title' => "Filtered Transaction {$i}",
            ]);
        }

        $this->get(route('admin.customers.transactions', [
            'q' => 'Filtered Transaction',
            'status' => 'won',
        ]))
            ->assertOk()
            ->assertSee('Filtered Transaction')
            ->assertSee('q=Filtered%20Transaction&amp;status=won&amp;page=2', false);
    }

    public function test_transaction_index_actions_respect_customer_permissions(): void
    {
        $transaction = CustomerTransaction::factory()->create([
            'title' => 'Permission Transaction',
        ]);
        $role = Role::create(['name' => 'transaction_viewer_only', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.customers.transactions'))
            ->assertOk()
            ->assertSee('Permission Transaction')
            ->assertDontSee('New Transaction')
            ->assertDontSee('data-customer-selector-trigger="newTransaction"', false)
            ->assertDontSee('data-customer-selector-modal="newTransaction"', false)
            ->assertDontSee(route('admin.customers.transactions.edit', $transaction), false)
            ->assertDontSee(route('admin.customers.transactions.destroy', $transaction), false)
            ->assertDontSee('Delete');
    }

    public function test_transaction_routes_reject_unauthorized_users(): void
    {
        $customer = Customer::factory()->create();
        $transaction = CustomerTransaction::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.transactions'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.transactions.create', $customer))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.customers.transactions.store', $customer), [
                'customer_id' => $customer->id,
                'title' => 'Unauthorized Create',
                'amount' => 1000,
                'status' => 'pending',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.transactions.edit', $transaction))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.customers.transactions.update', $transaction), [
                'customer_id' => $transaction->customer_id,
                'title' => 'Unauthorized Update',
                'amount' => 1000,
                'status' => 'pending',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.customers.transactions.destroy', $transaction))
            ->assertForbidden();
    }

    public function test_transaction_empty_state_respects_create_permission(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.transactions', ['q' => 'missing transaction']))
            ->assertOk()
            ->assertSee('No Transactions Yet')
            ->assertSee('Customer revenue and deal history will appear here.')
            ->assertSee('data-customer-selector-trigger="newTransaction"', false)
            ->assertSee('data-url="'.route('admin.customers.transactions.create', ['customer' => $customer]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.transactions.create', ['customer' => $customer]).'"', false);
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
