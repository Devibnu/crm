<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerInteractionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_interaction_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.interactions'));

        $response
            ->assertOk()
            ->assertSee('Interaction History')
            ->assertSee('CUSTOMER PROFILE 360')
            ->assertSee('Total Interactions')
            ->assertSee('Calls')
            ->assertSee('Meetings')
            ->assertSee('Latest Activity')
            ->assertSee('Search interactions...')
            ->assertSee('All Types')
            ->assertSee('New Interaction')
            ->assertDontSee('Riwayat interaksi customer: call, WhatsApp, email, meeting, note, dan follow-up.');
    }

    public function test_customer_scoped_interaction_create_page_shows_locked_customer_context(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'PT Krakatau Steel',
            'company_name' => 'Krakatau Group',
            'email' => 'care@krakatau.test',
        ]);

        $response = $this->get(route('admin.customers.interactions.create', $customer));

        $response
            ->assertOk()
            ->assertSee('CUSTOMER INTERACTION')
            ->assertSee('Add Interaction')
            ->assertSee('PT Krakatau Steel')
            ->assertSee('Krakatau Group')
            ->assertSee('care@krakatau.test')
            ->assertSee('Customer Context')
            ->assertSee('Interaction Details')
            ->assertSee('Activity Information')
            ->assertSee('name="customer_id"', false)
            ->assertSee('value="'.$customer->id.'"', false)
            ->assertSee(route('admin.customers.interactions', ['q' => $customer->name]), false)
            ->assertDontSee('<select name="customer_id"', false)
            ->assertDontSee('Tambahkan catatan interaksi customer untuk histori komunikasi yang lebih rapi.');
    }

    public function test_interaction_index_uses_customer_selector_modal_for_create_flow(): void
    {
        $customerA = Customer::factory()->create([
            'name' => 'Interaction Selector A',
            'company_name' => 'Alpha Interaction',
            'email' => 'interaction-a@example.test',
        ]);
        $customerB = Customer::factory()->create([
            'name' => 'Interaction Selector B',
            'company_name' => 'Beta Interaction',
            'phone' => '0811111111',
        ]);

        $this->get(route('admin.customers.interactions'))
            ->assertOk()
            ->assertSee('data-customer-selector-trigger="newInteraction"', false)
            ->assertSee('data-customer-selector-modal="newInteraction"', false)
            ->assertSee('Select a customer before creating an interaction record.')
            ->assertSee('data-customer-selector-continue disabled', false)
            ->assertSee('Interaction Selector A')
            ->assertSee('Interaction Selector B')
            ->assertSee('data-url="'.route('admin.customers.interactions.create', ['customer' => $customerA]).'"', false)
            ->assertSee('data-url="'.route('admin.customers.interactions.create', ['customer' => $customerB]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.interactions.create', ['customer' => $customerA]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.interactions.create', ['customer' => $customerB]).'"', false);
    }

    public function test_interaction_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'type' => 'call',
            'subject' => 'Call follow up pelanggan A',
            'description' => 'Customer meminta proposal terbaru.',
            'interaction_at' => '2026-05-01 10:00:00',
            'handled_by' => 'Admin One',
            'outcome' => 'Pending follow-up',
        ];

        $response = $this->post(route('admin.customers.interactions.store', $customer), $payload);

        $response->assertRedirect(route('admin.customers.interactions'));

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'type' => 'call',
            'subject' => 'Call follow up pelanggan A',
        ]);
    }

    public function test_customer_scoped_interaction_cannot_be_saved_to_a_different_customer(): void
    {
        $routeCustomer = Customer::factory()->create();
        $submittedCustomer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.interactions.create', $routeCustomer))
            ->post(route('admin.customers.interactions.store', $routeCustomer), [
                'customer_id' => $submittedCustomer->id,
                'type' => 'call',
                'subject' => 'Mismatched customer attempt',
            ]);

        $response
            ->assertRedirect(route('admin.customers.interactions.create', $routeCustomer))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseMissing('customer_interactions', [
            'customer_id' => $submittedCustomer->id,
            'subject' => 'Mismatched customer attempt',
        ]);
    }

    public function test_interaction_optional_fields_remain_optional(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('admin.customers.interactions.store', $customer), [
            'customer_id' => $customer->id,
            'type' => 'note',
            'subject' => 'Minimal interaction record',
        ]);

        $response->assertRedirect(route('admin.customers.interactions'));

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'type' => 'note',
            'subject' => 'Minimal interaction record',
            'description' => null,
            'interaction_at' => null,
            'handled_by' => null,
            'outcome' => null,
        ]);
    }

    public function test_interaction_validation_errors_preserve_old_input(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.interactions.create', $customer))
            ->post(route('admin.customers.interactions.store', $customer), [
                'customer_id' => $customer->id,
                'type' => 'email',
                'subject' => '',
                'description' => 'Preserved draft description',
                'handled_by' => 'Support Agent',
            ]);

        $response
            ->assertRedirect(route('admin.customers.interactions.create', $customer))
            ->assertSessionHasErrors('subject')
            ->assertSessionHasInput('description', 'Preserved draft description')
            ->assertSessionHasInput('handled_by', 'Support Agent');
    }

    public function test_user_without_interaction_create_permission_cannot_open_create_page(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.interactions.create', $customer))
            ->assertForbidden();
    }

    public function test_interaction_create_page_marks_interaction_history_sidebar_active(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->get(route('admin.customers.interactions.create', $customer));

        $content = $response->getContent();

        $response->assertOk();
        $this->assertStringContainsString(
            'href="'.route('admin.customers.interactions').'" class="nav-link parent compact active"',
            $content
        );
        $this->assertStringNotContainsString(
            'href="'.route('admin.customers.index').'" class="nav-link parent compact active"',
            $content
        );
    }

    public function test_interaction_edit_page_is_accessible(): void
    {
        $customer = Customer::factory()->create(['name' => 'Editable Interaction Customer']);
        $interaction = CustomerInteraction::factory()->create([
            'customer_id' => $customer->id,
            'type' => 'meeting',
            'subject' => 'Editable interaction subject',
        ]);

        $this->get(route('admin.customers.interactions.edit', $interaction))
            ->assertOk()
            ->assertSee('INTERACTION HISTORY')
            ->assertSee('Edit Interaction')
            ->assertSee('Editable Interaction Customer')
            ->assertSee('Meeting')
            ->assertSee('Customer Context')
            ->assertSee('Interaction Details')
            ->assertSee('Activity Information')
            ->assertSee('<select name="customer_id" required>', false)
            ->assertSee(route('admin.customers.interactions'), false)
            ->assertDontSee('Perbarui detail interaction agar histori customer tetap akurat.');
    }

    public function test_interaction_can_be_updated(): void
    {
        $interaction = CustomerInteraction::factory()->create([
            'type' => 'note',
            'subject' => 'Initial note',
        ]);

        $updatedCustomer = Customer::factory()->create();

        $response = $this->put(route('admin.customers.interactions.update', $interaction), [
            'customer_id' => $updatedCustomer->id,
            'type' => 'email',
            'subject' => 'Updated subject',
            'description' => 'Updated description',
            'interaction_at' => '2026-05-02 09:30:00',
            'handled_by' => 'Admin Two',
            'outcome' => 'Resolved',
        ]);

        $response->assertRedirect(route('admin.customers.interactions'));

        $this->assertDatabaseHas('customer_interactions', [
            'id' => $interaction->id,
            'customer_id' => $updatedCustomer->id,
            'type' => 'email',
            'subject' => 'Updated subject',
        ]);
    }

    public function test_interaction_update_validation_errors_preserve_old_input(): void
    {
        $interaction = CustomerInteraction::factory()->create();

        $response = $this->from(route('admin.customers.interactions.edit', $interaction))
            ->put(route('admin.customers.interactions.update', $interaction), [
                'customer_id' => $interaction->customer_id,
                'type' => 'follow_up',
                'subject' => '',
                'description' => 'Preserved edit description',
                'handled_by' => 'Edit Agent',
            ]);

        $response
            ->assertRedirect(route('admin.customers.interactions.edit', $interaction))
            ->assertSessionHasErrors('subject')
            ->assertSessionHasInput('description', 'Preserved edit description')
            ->assertSessionHasInput('handled_by', 'Edit Agent');
    }

    public function test_user_without_interaction_update_permission_cannot_open_edit_page(): void
    {
        $interaction = CustomerInteraction::factory()->create();
        $role = Role::create(['name' => 'interaction_creator_only', 'guard_name' => 'web']);
        $role->syncPermissions(['interactions.view', 'interactions.create']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.customers.interactions.edit', $interaction))
            ->assertForbidden();
    }

    public function test_interaction_can_be_deleted(): void
    {
        $interaction = CustomerInteraction::factory()->create();

        $response = $this->delete(route('admin.customers.interactions.destroy', $interaction));

        $response->assertRedirect(route('admin.customers.interactions'));

        $this->assertDatabaseMissing('customer_interactions', [
            'id' => $interaction->id,
        ]);
    }

    public function test_interaction_search_works(): void
    {
        $targetCustomer = Customer::factory()->create(['name' => 'Search Target Customer']);

        CustomerInteraction::factory()->create([
            'customer_id' => $targetCustomer->id,
            'subject' => 'Need quotation update',
            'description' => 'Customer asks for revised quote.',
            'handled_by' => 'Agent Search',
        ]);

        CustomerInteraction::factory()->create([
            'subject' => 'Unrelated subject',
            'handled_by' => 'Other Agent',
        ]);

        $this->get(route('admin.customers.interactions', ['q' => 'quotation update']))
            ->assertOk()
            ->assertSee('Need quotation update')
            ->assertDontSee('Unrelated subject');

        $this->get(route('admin.customers.interactions', ['q' => 'Search Target Customer']))
            ->assertOk()
            ->assertSee('Need quotation update');
    }

    public function test_interaction_type_filter_works(): void
    {
        CustomerInteraction::factory()->create([
            'type' => 'call',
            'subject' => 'Call interaction',
        ]);

        CustomerInteraction::factory()->create([
            'type' => 'email',
            'subject' => 'Email interaction',
        ]);

        $this->get(route('admin.customers.interactions', ['type' => 'call']))
            ->assertOk()
            ->assertSee('Call interaction')
            ->assertDontSee('Email interaction');
    }

    public function test_interaction_index_pagination_preserves_filters(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            CustomerInteraction::factory()->create([
                'type' => 'email',
                'subject' => "Filtered email interaction {$i}",
            ]);
        }

        $response = $this->get(route('admin.customers.interactions', [
            'q' => 'Filtered email',
            'type' => 'email',
        ]));

        $response
            ->assertOk()
            ->assertSee('Filtered email interaction')
            ->assertSee('q=Filtered%20email&amp;type=email&amp;page=2', false);
    }

    public function test_interaction_index_actions_respect_permissions(): void
    {
        $interaction = CustomerInteraction::factory()->create([
            'subject' => 'Permission scoped interaction',
        ]);
        $role = Role::create(['name' => 'interaction_reader_only', 'guard_name' => 'web']);
        $role->syncPermissions(['interactions.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.customers.interactions'))
            ->assertOk()
            ->assertSee('Permission scoped interaction')
            ->assertDontSee('New Interaction')
            ->assertDontSee('data-customer-selector-trigger="newInteraction"', false)
            ->assertDontSee('data-customer-selector-modal="newInteraction"', false)
            ->assertDontSee(route('admin.customers.interactions.edit', $interaction), false)
            ->assertDontSee(route('admin.customers.interactions.destroy', $interaction), false)
            ->assertDontSee('Delete');
    }

    public function test_interaction_index_empty_state_is_compact_and_action_aware(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.interactions', ['q' => 'missing interaction']))
            ->assertOk()
            ->assertSee('No Interactions Yet')
            ->assertSee('Customer communication history will appear here.')
            ->assertSee('data-customer-selector-trigger="newInteraction"', false)
            ->assertSee('data-url="'.route('admin.customers.interactions.create', ['customer' => $customer]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.interactions.create', ['customer' => $customer]).'"', false);
    }

    public function test_customer_show_page_displays_latest_interactions(): void
    {
        $customer = Customer::factory()->create();

        $oldest = CustomerInteraction::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Oldest interaction',
            'interaction_at' => Carbon::parse('2026-01-01 08:00:00'),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            CustomerInteraction::factory()->create([
                'customer_id' => $customer->id,
                'subject' => "Newest interaction {$i}",
                'interaction_at' => Carbon::parse('2026-01-01 08:00:00')->addDays($i),
            ]);
        }

        $response = $this->get(route('admin.customers.show', $customer));

        $response
            ->assertOk()
            ->assertSee('Newest interaction 1')
            ->assertSee('Newest interaction 2')
            ->assertSee('Newest interaction 3')
            ->assertSee('Newest interaction 4')
            ->assertSee('Newest interaction 5')
            ->assertDontSee($oldest->subject);
    }
}
