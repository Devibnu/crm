<?php

namespace App\Http\Controllers\Admin;

use App\Events\Omnichannel\ConversationNoteCreated;
use App\Events\Omnichannel\ConversationUpdated;
use App\Http\Controllers\Controller;
use App\Models\ConversationNote;
use App\Models\WhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationNoteController extends Controller
{
    public function index(WhatsAppConversation $conversation): JsonResponse
    {
        $notes = $conversation->internalNotes()
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (ConversationNote $note): array => $this->notePayload($note));

        return response()->json(['data' => $notes]);
    }

    public function store(Request $request, WhatsAppConversation $conversation): JsonResponse
    {
        $data = $this->validatedData($request);
        $note = $conversation->internalNotes()->create([
            'user_id' => $request->user()->id,
            'note' => $data['note'],
        ]);

        ConversationNoteCreated::dispatch($conversation->id, $note->id);
        ConversationUpdated::dispatch($conversation->id);

        return response()->json([
            'message' => 'Catatan internal berhasil disimpan.',
            'data' => $this->notePayload($note->load('user:id,name')),
        ], 201);
    }

    public function update(Request $request, ConversationNote $note): JsonResponse
    {
        $note->update($this->validatedData($request));

        return response()->json([
            'message' => 'Catatan internal berhasil diperbarui.',
            'data' => $this->notePayload($note->load('user:id,name')),
        ]);
    }

    public function destroy(ConversationNote $note): JsonResponse
    {
        $note->delete();

        return response()->json(['message' => 'Catatan internal berhasil dihapus.']);
    }

    /** @return array{note: string} */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);
    }

    /** @return array<string, mixed> */
    private function notePayload(ConversationNote $note): array
    {
        return [
            'id' => $note->id,
            'note' => $note->note,
            'user' => [
                'id' => $note->user?->id,
                'name' => $note->user?->name ?? 'User CRM',
            ],
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
            'update_url' => route('admin.service.omnichannel.notes.update', $note),
            'delete_url' => route('admin.service.omnichannel.notes.destroy', $note),
        ];
    }
}
