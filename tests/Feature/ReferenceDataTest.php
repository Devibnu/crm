<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerSatisfaction;
use App\Models\ReferenceType;
use App\Models\ReferenceValue;
use App\Models\Ticket;
use App\Services\ReferenceData\ReferenceDataService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_reference_data_seeder_creates_service_channels_idempotently(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(ReferenceDataSeeder::class);

        $type = ReferenceType::query()->where('code', 'service_channel')->firstOrFail();

        $this->assertDatabaseCount('reference_types', 1);
        $this->assertSame('Service Channels', $type->name);
        $this->assertSame(4, $type->governance_level);
        $this->assertCount(15, $type->values);
        $this->assertDatabaseHas('reference_values', [
            'reference_type_id' => $type->id,
            'code' => 'whatsapp',
            'label' => 'WhatsApp',
            'is_system' => true,
        ]);
        $this->assertDatabaseHas('reference_value_capabilities', [
            'reference_value_id' => ReferenceValue::query()->where('code', 'whatsapp')->value('id'),
            'capability' => 'requires_provider',
        ]);
    }

    public function test_reference_codes_are_immutable_and_system_values_cannot_be_deleted(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $value = ReferenceValue::query()->where('code', 'email')->firstOrFail();

        $this->expectException(ValidationException::class);
        $value->update(['code' => 'email_new']);
    }

    public function test_system_reference_value_cannot_be_deleted(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $value = ReferenceValue::query()->where('code', 'email')->firstOrFail();

        $this->expectException(ValidationException::class);
        $value->delete();
    }

    public function test_reference_service_filters_options_by_capability(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $service = app(ReferenceDataService::class);

        $ticketChannels = $service->options(ReferenceDataService::TYPE_SERVICE_CHANNEL, 'service_ticket');
        $csatChannels = $service->options(ReferenceDataService::TYPE_SERVICE_CHANNEL, 'csat_survey');

        $this->assertArrayHasKey('walk_in', $ticketChannels);
        $this->assertArrayHasKey('social', $ticketChannels);
        $this->assertArrayNotHasKey('sms', $ticketChannels);
        $this->assertArrayHasKey('phone', $csatChannels);
        $this->assertArrayNotHasKey('walk_in', $csatChannels);
    }

    public function test_ticket_dropdown_uses_service_ticket_channels_when_references_exist(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $this->get(route('admin.service.tickets.create'))
            ->assertOk()
            ->assertSee('value="walk_in"', false)
            ->assertSee('Walk-in')
            ->assertDontSee('value="sms"', false)
            ->assertDontSee('value="livechat"', false);
    }

    public function test_csat_dropdown_uses_csat_survey_channels_when_references_exist(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $this->get(route('admin.service.customer-satisfaction.create'))
            ->assertOk()
            ->assertSee('value="email"', false)
            ->assertSee('WhatsApp')
            ->assertDontSee('value="walk_in"', false)
            ->assertDontSee('value="sms"', false);
    }

    public function test_existing_string_code_remains_stored_for_ticket_and_csat(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $customer = Customer::factory()->create();

        $ticketResponse = $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => 'Channel storage check',
            'description' => 'Stores legacy channel code.',
            'priority' => 'medium',
            'status' => 'open',
            'channel' => 'whatsapp',
        ]);

        $ticket = Ticket::query()->where('subject', 'Channel storage check')->firstOrFail();
        $ticketResponse->assertRedirect(route('admin.service.tickets.show', $ticket));
        $this->assertSame('whatsapp', $ticket->channel);

        $csatResponse = $this->post(route('admin.service.customer-satisfaction.store'), [
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'feedback' => 'Channel storage CSAT.',
            'survey_channel' => 'whatsapp',
            'sentiment' => 'positive',
            'follow_up_required' => 0,
        ]);

        $satisfaction = CustomerSatisfaction::query()->where('feedback', 'Channel storage CSAT.')->firstOrFail();
        $csatResponse->assertRedirect(route('admin.service.customer-satisfaction.show', $satisfaction));
        $this->assertSame('whatsapp', $satisfaction->survey_channel);
    }

    public function test_invalid_capability_combination_is_rejected_when_reference_exists(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $email = ReferenceValue::query()->where('code', 'email')->firstOrFail();
        $email->capabilities()->where('capability', 'service_ticket')->delete();
        Cache::flush();

        $this->post(route('admin.service.tickets.store'), [
            'subject' => 'Invalid capability',
            'priority' => 'medium',
            'status' => 'open',
            'channel' => 'email',
        ])->assertSessionHasErrors('channel');
    }

    public function test_existing_inactive_channel_remains_valid_when_unchanged_on_update(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $ticket = Ticket::factory()->create(['channel' => 'web', 'status' => 'open']);
        ReferenceValue::query()->where('code', 'web')->update(['is_active' => false]);
        Cache::flush();

        $this->put(route('admin.service.tickets.update', $ticket), [
            'customer_id' => $ticket->customer_id,
            'subject' => 'Updated inactive existing channel',
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => 'in_progress',
            'channel' => 'web',
            'assigned_to' => $ticket->assigned_to,
        ])->assertRedirect(route('admin.service.tickets.show', $ticket));

        $this->assertSame('web', $ticket->refresh()->channel);
    }

    public function test_legacy_fallback_options_work_when_reference_table_is_empty(): void
    {
        $this->assertDatabaseCount('reference_values', 0);

        $this->get(route('admin.service.tickets.create'))
            ->assertOk()
            ->assertSee('value="walk_in"', false);

        $this->get(route('admin.service.customer-satisfaction.create'))
            ->assertOk()
            ->assertSee('value="web"', false);
    }
}
