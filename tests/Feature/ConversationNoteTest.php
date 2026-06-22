<?php

namespace Tests\Feature;

use App\Models\ConversationNote;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConversationNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_create_internal_note(): void
    {
        $conversation = $this->conversation();

        $response = $this->postJson(route('admin.service.omnichannel.notes.store', $conversation), [
            'note' => 'Customer meminta follow up internal besok pagi.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Catatan internal berhasil disimpan.')
            ->assertJsonPath('data.note', 'Customer meminta follow up internal besok pagi.');

        $this->assertDatabaseHas('conversation_notes', [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'note' => 'Customer meminta follow up internal besok pagi.',
        ]);
    }

    public function test_user_with_permission_can_update_internal_note(): void
    {
        $note = $this->note();

        $this->putJson(route('admin.service.omnichannel.notes.update', $note), [
            'note' => 'Catatan internal telah diperbarui.',
        ])->assertOk()
            ->assertJsonPath('data.note', 'Catatan internal telah diperbarui.');

        $this->assertDatabaseHas('conversation_notes', [
            'id' => $note->id,
            'note' => 'Catatan internal telah diperbarui.',
        ]);
    }

    public function test_user_with_permission_can_delete_internal_note(): void
    {
        $note = $this->note();

        $this->deleteJson(route('admin.service.omnichannel.notes.destroy', $note))
            ->assertOk()
            ->assertJsonPath('message', 'Catatan internal berhasil dihapus.');

        $this->assertDatabaseMissing('conversation_notes', ['id' => $note->id]);
    }

    public function test_notes_are_returned_latest_first_for_user_with_view_permission(): void
    {
        $conversation = $this->conversation();
        $older = $this->note($conversation, 'Catatan lama');
        $older->forceFill(['created_at' => now()->subHour()])->save();
        $newer = $this->note($conversation, 'Catatan terbaru');
        $role = Role::create(['name' => 'notes_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['omnichannel_notes.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson(route('admin.service.omnichannel.notes.index', $conversation))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_guest_cannot_access_internal_notes(): void
    {
        $conversation = $this->conversation();
        auth()->logout();

        $this->get(route('admin.service.omnichannel.notes.index', $conversation))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $conversation = $this->conversation();
        $role = Role::create(['name' => 'notes_denied', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson(route('admin.service.omnichannel.notes.index', $conversation))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('admin.service.omnichannel.notes.store', $conversation), ['note' => 'Tidak boleh tersimpan'])
            ->assertForbidden();
    }

    public function test_note_validation_requires_note_and_limits_length(): void
    {
        $conversation = $this->conversation();

        $this->postJson(route('admin.service.omnichannel.notes.store', $conversation), ['note' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');

        $this->postJson(route('admin.service.omnichannel.notes.store', $conversation), ['note' => str_repeat('a', 5001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
    }

    public function test_omnichannel_workspace_renders_internal_notes_tab_and_ajax_endpoint(): void
    {
        $conversation = $this->conversation();
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => $conversation->phone_number,
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Pesan customer untuk membuka workspace.',
            'status' => 'delivered',
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('data-omni-profile-tab="notes"', false)
            ->assertSee('Internal Notes')
            ->assertSee('Tulis catatan internal...')
            ->assertSee(route('admin.service.omnichannel.notes.index', $conversation), false)
            ->assertSee(route('admin.service.omnichannel.notes.store', $conversation), false);
    }

    private function conversation(): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'contact_name' => 'Internal Notes Customer',
            'phone_number' => fake()->unique()->numerify('62812########'),
            'channel' => 'whatsapp',
            'status' => 'open',
        ]);
    }

    private function note(?WhatsAppConversation $conversation = null, string $content = 'Catatan internal'): ConversationNote
    {
        return ConversationNote::create([
            'conversation_id' => ($conversation ?? $this->conversation())->id,
            'user_id' => auth()->id(),
            'note' => $content,
        ]);
    }
}
