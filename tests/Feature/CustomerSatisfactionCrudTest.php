<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerSatisfaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSatisfactionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_satisfaction_index_is_accessible(): void
    {
        $this->get(route('admin.service.customer-satisfaction.index'))
            ->assertOk()
            ->assertSee('Customer Satisfaction')
            ->assertSee('Kelola survei kepuasan pelanggan dan tindak lanjut feedback.');
    }

    public function test_customer_satisfaction_create_is_accessible(): void
    {
        $this->get(route('admin.service.customer-satisfaction.create'))
            ->assertOk()
            ->assertSee('Add Customer Satisfaction')
            ->assertSee('Pilih customer terlebih dahulu')
            ->assertSee('data-csat-ticket-select', false);
    }

    public function test_customer_satisfaction_can_be_created(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        $response = $this->post(route('admin.service.customer-satisfaction.store'), [
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'feedback' => 'Great support experience.',
            'survey_channel' => 'email',
            'sentiment' => 'positive',
            'submitted_at' => '2026-05-12T09:00',
            'follow_up_required' => 0,
            'follow_up_notes' => null,
        ]);

        $satisfaction = CustomerSatisfaction::query()->where('feedback', 'Great support experience.')->firstOrFail();

        $response->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));

        $this->assertDatabaseHas('customer_satisfactions', [
            'id' => $satisfaction->id,
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'survey_channel' => 'email',
            'sentiment' => 'positive',
            'follow_up_required' => false,
        ]);
    }

    public function test_customer_satisfaction_show_is_accessible(): void
    {
        $satisfaction = CustomerSatisfaction::factory()->create([
            'feedback' => 'Show customer satisfaction feedback.',
        ]);

        $this->get(route('admin.service.customer-satisfaction.show', $satisfaction))
            ->assertOk()
            ->assertSee('Customer Satisfaction Detail')
            ->assertSee('Show customer satisfaction feedback.');
    }

    public function test_customer_satisfaction_edit_is_accessible(): void
    {
        $satisfaction = CustomerSatisfaction::factory()->create();

        $this->get(route('admin.service.customer-satisfaction.edit', $satisfaction))
            ->assertOk()
            ->assertSee('Edit Customer Satisfaction');
    }

    public function test_customer_satisfaction_can_be_updated(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $satisfaction = CustomerSatisfaction::factory()->create([
            'rating' => 3,
            'sentiment' => 'neutral',
        ]);

        $response = $this->put(route('admin.service.customer-satisfaction.update', $satisfaction), [
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 2,
            'feedback' => 'Needs follow up.',
            'survey_channel' => 'whatsapp',
            'sentiment' => 'negative',
            'submitted_at' => '2026-05-13T10:00',
            'follow_up_required' => 1,
            'follow_up_notes' => 'Call customer back.',
        ]);

        $response->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));

        $this->assertDatabaseHas('customer_satisfactions', [
            'id' => $satisfaction->id,
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 2,
            'survey_channel' => 'whatsapp',
            'sentiment' => 'negative',
            'follow_up_required' => true,
        ]);
    }

    public function test_customer_satisfaction_can_be_deleted(): void
    {
        $satisfaction = CustomerSatisfaction::factory()->create();

        $response = $this->delete(route('admin.service.customer-satisfaction.destroy', $satisfaction));

        $response->assertRedirect(route('admin.service.customer-satisfaction.index'));

        $this->assertDatabaseMissing('customer_satisfactions', [
            'id' => $satisfaction->id,
        ]);
    }

    public function test_customer_satisfaction_search_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Searchable CSAT Customer']);
        $ticket = Ticket::factory()->create(['ticket_number' => 'TCK-CSAT-SEARCH']);
        $match = CustomerSatisfaction::factory()->create([
            'customer_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'feedback' => 'Searchable CSAT feedback.',
        ]);
        $other = CustomerSatisfaction::factory()->create([
            'customer_id' => null,
            'ticket_id' => null,
            'feedback' => 'Other CSAT feedback.',
        ]);

        $this->get(route('admin.service.customer-satisfaction.index', ['q' => 'Searchable CSAT Customer']))
            ->assertOk()
            ->assertSee((string) $match->rating.'/5')
            ->assertSee('Searchable CSAT Customer')
            ->assertDontSee('Other CSAT feedback.');

        $this->get(route('admin.service.customer-satisfaction.index', ['q' => 'TCK-CSAT-SEARCH']))
            ->assertOk()
            ->assertSee('TCK-CSAT-SEARCH')
            ->assertDontSee('Other CSAT feedback.');
    }

    public function test_customer_satisfaction_rating_filter_works(): void
    {
        $five = CustomerSatisfaction::factory()->create(['feedback' => 'Five Rating Filter', 'rating' => 5]);
        $one = CustomerSatisfaction::factory()->create(['feedback' => 'One Rating Filter', 'rating' => 1]);

        $this->get(route('admin.service.customer-satisfaction.index', ['rating' => 5]))
            ->assertOk()
            ->assertSee($five->feedback)
            ->assertDontSee($one->feedback);
    }

    public function test_customer_satisfaction_sentiment_filter_works(): void
    {
        $positive = CustomerSatisfaction::factory()->create(['feedback' => 'Positive Sentiment Filter', 'sentiment' => 'positive']);
        $negative = CustomerSatisfaction::factory()->create(['feedback' => 'Negative Sentiment Filter', 'sentiment' => 'negative']);

        $this->get(route('admin.service.customer-satisfaction.index', ['sentiment' => 'positive']))
            ->assertOk()
            ->assertSee($positive->feedback)
            ->assertDontSee($negative->feedback);
    }

    public function test_customer_satisfaction_channel_filter_works(): void
    {
        $email = CustomerSatisfaction::factory()->create(['feedback' => 'Email Channel Filter', 'survey_channel' => 'email']);
        $web = CustomerSatisfaction::factory()->create(['feedback' => 'Web Channel Filter', 'survey_channel' => 'web']);

        $this->get(route('admin.service.customer-satisfaction.index', ['survey_channel' => 'email']))
            ->assertOk()
            ->assertSee($email->feedback)
            ->assertDontSee($web->feedback);
    }

    public function test_customer_satisfaction_follow_up_filter_works(): void
    {
        $required = CustomerSatisfaction::factory()->create(['feedback' => 'Follow Up Required Filter', 'follow_up_required' => true]);
        $notRequired = CustomerSatisfaction::factory()->create(['feedback' => 'No Follow Up Filter', 'follow_up_required' => false]);

        $this->get(route('admin.service.customer-satisfaction.index', ['follow_up_required' => 'yes']))
            ->assertOk()
            ->assertSee($required->feedback)
            ->assertDontSee($notRequired->feedback);

        $this->get(route('admin.service.customer-satisfaction.index', ['follow_up_required' => 'no']))
            ->assertOk()
            ->assertSee($notRequired->feedback)
            ->assertDontSee($required->feedback);
    }

    public function test_customer_ticket_endpoint_returns_only_tickets_for_selected_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $latestTicket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TCK-CSAT-LATEST',
            'subject' => 'Latest customer ticket',
            'created_at' => now(),
        ]);
        $olderTicket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TCK-CSAT-OLDER',
            'subject' => 'Older customer ticket',
            'created_at' => now()->subDay(),
        ]);
        $otherTicket = Ticket::factory()->create([
            'customer_id' => $otherCustomer->id,
            'ticket_number' => 'TCK-CSAT-OTHER',
            'subject' => 'Other customer ticket',
        ]);

        $this->getJson(route('admin.service.customer-satisfaction.customer-tickets', $customer))
            ->assertOk()
            ->assertJsonPath('data.0.id', $latestTicket->id)
            ->assertJsonPath('data.0.ticket_number', 'TCK-CSAT-LATEST')
            ->assertJsonPath('data.1.id', $olderTicket->id)
            ->assertJsonMissing(['id' => $otherTicket->id])
            ->assertJsonMissing(['ticket_number' => 'TCK-CSAT-OTHER']);
    }

    public function test_customer_ticket_endpoint_is_authentication_and_permission_protected(): void
    {
        $customer = Customer::factory()->create();

        auth()->guard('web')->logout();

        $this->getJson(route('admin.service.customer-satisfaction.customer-tickets', $customer))
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.service.customer-satisfaction.customer-tickets', $customer))
            ->assertForbidden();
    }

    public function test_customer_satisfaction_can_be_created_without_ticket(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->post(route('admin.service.customer-satisfaction.store'), [
            'ticket_id' => null,
            'customer_id' => $customer->id,
            'rating' => 4,
            'feedback' => 'Good service without ticket.',
            'survey_channel' => 'web',
            'sentiment' => 'positive',
            'submitted_at' => '2026-05-14T09:00',
            'follow_up_required' => 0,
            'follow_up_notes' => null,
        ]);

        $satisfaction = CustomerSatisfaction::query()->where('feedback', 'Good service without ticket.')->firstOrFail();

        $response->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));

        $this->assertDatabaseHas('customer_satisfactions', [
            'id' => $satisfaction->id,
            'ticket_id' => null,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_customer_satisfaction_store_rejects_ticket_from_another_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $otherTicket = Ticket::factory()->create(['customer_id' => $otherCustomer->id]);

        $this->from(route('admin.service.customer-satisfaction.create'))
            ->post(route('admin.service.customer-satisfaction.store'), [
                'ticket_id' => $otherTicket->id,
                'customer_id' => $customer->id,
                'rating' => 2,
                'feedback' => 'Invalid linked ticket.',
                'survey_channel' => 'email',
                'sentiment' => 'negative',
                'submitted_at' => '2026-05-15T09:00',
                'follow_up_required' => 1,
                'follow_up_notes' => 'Needs correction.',
            ])
            ->assertRedirect(route('admin.service.customer-satisfaction.create'))
            ->assertSessionHasErrors('ticket_id');

        $this->assertDatabaseMissing('customer_satisfactions', [
            'feedback' => 'Invalid linked ticket.',
        ]);
    }

    public function test_customer_satisfaction_can_be_updated_without_ticket(): void
    {
        $customer = Customer::factory()->create();
        $satisfaction = CustomerSatisfaction::factory()->create();

        $response = $this->put(route('admin.service.customer-satisfaction.update', $satisfaction), [
            'ticket_id' => null,
            'customer_id' => $customer->id,
            'rating' => 5,
            'feedback' => 'Updated without ticket.',
            'survey_channel' => 'phone',
            'sentiment' => 'positive',
            'submitted_at' => '2026-05-16T09:00',
            'follow_up_required' => 0,
            'follow_up_notes' => null,
        ]);

        $response->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));

        $this->assertDatabaseHas('customer_satisfactions', [
            'id' => $satisfaction->id,
            'ticket_id' => null,
            'customer_id' => $customer->id,
            'feedback' => 'Updated without ticket.',
        ]);
    }

    public function test_customer_satisfaction_can_be_updated_with_ticket_from_selected_customer(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $satisfaction = CustomerSatisfaction::factory()->create();

        $response = $this->put(route('admin.service.customer-satisfaction.update', $satisfaction), [
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 4,
            'feedback' => 'Updated with matching ticket.',
            'survey_channel' => 'whatsapp',
            'sentiment' => 'positive',
            'submitted_at' => '2026-05-17T09:00',
            'follow_up_required' => 0,
            'follow_up_notes' => null,
        ]);

        $response->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));

        $this->assertDatabaseHas('customer_satisfactions', [
            'id' => $satisfaction->id,
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_customer_satisfaction_update_rejects_ticket_from_another_customer(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $otherTicket = Ticket::factory()->create(['customer_id' => $otherCustomer->id]);
        $satisfaction = CustomerSatisfaction::factory()->create([
            'feedback' => 'Original feedback.',
        ]);

        $this->from(route('admin.service.customer-satisfaction.edit', $satisfaction))
            ->put(route('admin.service.customer-satisfaction.update', $satisfaction), [
                'ticket_id' => $otherTicket->id,
                'customer_id' => $customer->id,
                'rating' => 1,
                'feedback' => 'Should not persist.',
                'survey_channel' => 'email',
                'sentiment' => 'negative',
                'submitted_at' => '2026-05-18T09:00',
                'follow_up_required' => 1,
                'follow_up_notes' => 'Invalid ticket.',
            ])
            ->assertRedirect(route('admin.service.customer-satisfaction.edit', $satisfaction))
            ->assertSessionHasErrors('ticket_id');

        $this->assertDatabaseMissing('customer_satisfactions', [
            'id' => $satisfaction->id,
            'feedback' => 'Should not persist.',
        ]);
    }
}
