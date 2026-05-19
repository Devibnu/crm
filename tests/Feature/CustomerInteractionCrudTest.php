<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerInteractionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_interaction_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.interactions'));

        $response
            ->assertOk()
            ->assertSee('Interaction History');
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

    public function test_interaction_edit_page_is_accessible(): void
    {
        $interaction = CustomerInteraction::factory()->create();

        $this->get(route('admin.customers.interactions.edit', $interaction))
            ->assertOk()
            ->assertSee('Edit Interaction');
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
