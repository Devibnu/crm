<?php

namespace Tests\Feature;

use App\Models\CustomerTimelineEvent;
use App\Models\Pelanggan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppsEmailApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_it_lists_customer_backed_emails_from_real_customer_data(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Rina Customer',
            'email' => 'rina@example.com',
            'no_hp' => '081234567890',
            'status' => 'active',
            'source' => 'campaign',
            'notes' => 'Butuh follow up penawaran enterprise.',
        ]);

        Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'billing',
            'subjek' => 'Tagihan tahunan enterprise',
            'status' => 'diproses',
            'prioritas' => 'tinggi',
        ]);

        $response = $this->getJson('/api/apps/email?q=rina@example.com');

        $response
            ->assertOk()
            ->assertJsonPath('emails.0.from.email', 'rina@example.com')
            ->assertJsonPath('emails.0.from.name', 'Rina Customer')
            ->assertJsonPath('emails.0.labels.0', 'personal');

        $this->assertSame('company', $response->json('emails.0.labels.1'));
        $this->assertSame('important', $response->json('emails.0.labels.2'));
    }

    public function test_it_can_update_email_flags_and_toggle_labels(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Dewi Inbox',
            'email' => 'dewi@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $emailId = 900000 + ($customer->id * 10);

        $this->postJson('/api/apps/email', [
            'ids' => [$emailId],
            'data' => [
                'isRead' => true,
                'isStarred' => true,
                'folder' => 'spam',
            ],
        ])->assertCreated();

        $this->postJson('/api/apps/email', [
            'ids' => [$emailId],
            'label' => 'private',
        ])->assertCreated();

        $response = $this->getJson('/api/apps/email?filter=spam&q=dewi@example.com');

        $response
            ->assertOk()
            ->assertJsonPath('emails.0.id', $emailId)
            ->assertJsonPath('emails.0.isRead', true)
            ->assertJsonPath('emails.0.isStarred', true)
            ->assertJsonPath('emails.0.folder', 'spam');

        $this->assertContains('private', $response->json('emails.0.labels'));

        $this->assertDatabaseHas('customer_timeline_events', [
            'customer_id' => $customer->id,
            'event_type' => 'email_moved',
        ]);

        $this->assertDatabaseHas('customer_timeline_events', [
            'customer_id' => $customer->id,
            'event_type' => 'email_starred',
        ]);

        $this->assertDatabaseHas('customer_timeline_events', [
            'customer_id' => $customer->id,
            'event_type' => 'email_label_added',
        ]);
    }

    public function test_it_can_send_email_and_attach_it_to_customer_timeline_feed(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Sari Follow Up',
            'email' => 'sari@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $response = $this->postJson('/api/apps/email/send', [
            'customerId' => $customer->id,
            'to' => [
                [
                    'email' => 'sari@example.com',
                    'name' => 'Sari Follow Up',
                ],
            ],
            'subject' => 'Penawaran lanjutan',
            'message' => '<p>Kami lanjutkan diskusi penawaran Anda.</p>',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.folder', 'sent')
            ->assertJsonPath('data.customerId', $customer->id)
            ->assertJsonPath('data.subject', 'Penawaran lanjutan');

        $sentResponse = $this->getJson('/api/apps/email?filter=sent&q=penawaran lanjutan');

        $sentResponse
            ->assertOk()
            ->assertJsonPath('emails.0.subject', 'Penawaran lanjutan');

        $timelineResponse = $this->getJson("/api/crm/pelanggan/{$customer->id}/timeline");

        $timelineResponse->assertOk();

        $timelineTypes = collect($timelineResponse->json('data'))->pluck('type')->all();

        $this->assertContains('email_sent', $timelineTypes);
    }
}