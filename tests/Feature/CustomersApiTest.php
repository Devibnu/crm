<?php

namespace Tests\Feature;

use App\Models\CustomerIdentity;
use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomersApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_it_lists_customers_with_compatible_summary_fields(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'no_hp' => '08123456789',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $customer->identities()->create([
            'type' => 'email',
            'value' => 'budi@example.com',
            'label' => 'primary',
            'is_primary' => true,
            'is_verified' => true,
        ]);

        $response = $this->getJson('/api/crm/pelanggan');

        $response
            ->assertOk()
            ->assertJsonPath('pelanggan.0.id', $customer->id)
            ->assertJsonPath('pelanggan.0.nama', 'Budi Santoso')
            ->assertJsonPath('pelanggan.0.status', 'active')
            ->assertJsonPath('data.0.primaryEmail', 'budi@example.com');
    }

    public function test_it_can_create_customer_and_backfill_primary_identities_and_timeline(): void
    {
        $response = $this->postJson('/api/crm/pelanggan', [
            'nama' => 'Customer Test',
            'email' => 'customer.test@example.com',
            'noHp' => '0812-0000-1111',
            'status' => 'active',
            'source' => 'manual',
            'notes' => 'Created by automated test',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nama', 'Customer Test')
            ->assertJsonPath('data.primaryEmail', 'customer.test@example.com')
            ->assertJsonPath('data.primaryWhatsapp', '0812-0000-1111');

        $customer = Pelanggan::query()->where('email', 'customer.test@example.com')->firstOrFail();

        $this->assertDatabaseHas('customer_identities', [
            'customer_id' => $customer->id,
            'type' => 'email',
            'value' => 'customer.test@example.com',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('customer_identities', [
            'customer_id' => $customer->id,
            'type' => 'whatsapp',
            'value' => '081200001111',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('customer_timeline_events', [
            'customer_id' => $customer->id,
            'event_type' => 'customer_created',
        ]);
    }

    public function test_it_can_show_customer_detail_with_identities_and_timeline(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Detail Customer',
            'email' => 'detail@example.com',
            'no_hp' => '0812333444',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $customer->identities()->create([
            'type' => 'email',
            'value' => 'detail@example.com',
            'label' => 'primary',
            'is_primary' => true,
            'is_verified' => true,
        ]);

        $customer->timelineEvents()->create([
            'user_id' => $this->user->id,
            'event_type' => 'customer_created',
            'title' => 'Customer dibuat',
            'description' => 'Initial timeline entry',
            'event_at' => now(),
        ]);

        $response = $this->getJson("/api/crm/pelanggan/{$customer->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.identities.0.value', 'detail@example.com')
            ->assertJsonPath('data.timeline.0.title', 'Customer dibuat');
    }

    public function test_it_can_update_customer_profile(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Old Name',
            'email' => 'old@example.com',
            'no_hp' => '08121111',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $customer->identities()->create([
            'type' => 'email',
            'value' => 'old@example.com',
            'label' => 'primary',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $response = $this->putJson("/api/crm/pelanggan/{$customer->id}", [
            'nama' => 'New Name',
            'email' => 'new@example.com',
            'noHp' => '0812999999',
            'status' => 'inactive',
            'source' => 'import',
            'notes' => 'Updated by test',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.nama', 'New Name')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.source', 'import');

        $this->assertDatabaseHas('pelanggan', [
            'id' => $customer->id,
            'nama' => 'New Name',
            'email' => 'new@example.com',
            'status' => 'inactive',
            'source' => 'import',
        ]);

        $this->assertDatabaseHas('customer_timeline_events', [
            'customer_id' => $customer->id,
            'event_type' => 'customer_updated',
        ]);
    }

    public function test_it_can_add_identity_and_block_duplicate_identity_values(): void
    {
        $firstCustomer = Pelanggan::query()->create([
            'nama' => 'First Customer',
            'email' => 'first@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $secondCustomer = Pelanggan::query()->create([
            'nama' => 'Second Customer',
            'email' => 'second@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $response = $this->postJson("/api/crm/pelanggan/{$firstCustomer->id}/identities", [
            'type' => 'whatsapp',
            'value' => '0812 8888 9999',
            'label' => 'main',
            'isPrimary' => true,
            'isVerified' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'whatsapp')
            ->assertJsonPath('data.value', '081288889999');

        $duplicateResponse = $this->postJson("/api/crm/pelanggan/{$secondCustomer->id}/identities", [
            'type' => 'whatsapp',
            'value' => '081288889999',
            'label' => 'main',
            'isPrimary' => true,
            'isVerified' => false,
        ]);

        $duplicateResponse
            ->assertStatus(422)
            ->assertJsonPath('message', 'Identity sudah digunakan oleh customer lain.');

        $this->assertDatabaseCount('customer_identities', 1);
    }

    public function test_it_can_fetch_customer_timeline_endpoint(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Timeline Customer',
            'email' => 'timeline@example.com',
            'no_hp' => '6281234567890',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Percakapan WhatsApp',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'isi_pesan' => 'Halo dari WhatsApp',
            'pengirim' => 'Timeline Customer',
        ]);

        $customer->timelineEvents()->create([
            'user_id' => $this->user->id,
            'event_type' => 'customer_created',
            'title' => 'Customer dibuat',
            'description' => 'Timeline smoke test',
            'event_at' => now(),
        ]);

        $response = $this->getJson("/api/crm/pelanggan/{$customer->id}/timeline");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.type', 'customer_created')
            ->assertJsonPath('data.0.user.id', $this->user->id);

        $timelineTypes = collect($response->json('data'))->pluck('type')->all();

        $this->assertContains('email_received', $timelineTypes);
        $this->assertContains('whatsapp_received', $timelineTypes);
    }
}