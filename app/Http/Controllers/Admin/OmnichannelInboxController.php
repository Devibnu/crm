<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OmnichannelInboxController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $channel = trim((string) $request->query('channel', ''));
        $status = trim((string) $request->query('status', ''));
        $inboxFilter = trim((string) $request->query('filter', 'semua'));

        $conversationsQuery = WhatsAppConversation::query()
            ->with([
                'customer:id,name,phone,whatsapp,status',
                'lead:id,name,phone,whatsapp,status,priority,assigned_to',
                'messages' => fn ($query) => $query->latest()->limit(80),
            ])
            ->whereHas('messages', fn (Builder $query) => $query->where('direction', 'inbound'))
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($status !== '' && in_array($status, $this->conversationStatusOptions(), true), fn ($query) => $query->where('status', $status))
            ->when($inboxFilter === 'belum-diambil', fn ($query) => $query->whereNull('assigned_to'))
            ->when($inboxFilter === 'milik-saya', fn ($query) => $query->where('assigned_to', auth()->user()?->name))
            ->latest('last_message_at')
            ->latest();
        $conversations = $conversationsQuery->get();
        $selectedConversationId = (int) $request->query('conversation', 0);
        $selectedConversation = $selectedConversationId > 0
            ? $conversations->firstWhere('id', $selectedConversationId)
            : $conversations->first();

        $this->markSelectedConversationAsRead($selectedConversation);

        $summary = [
            'total' => $this->realConversationQuery()->count(),
            'open' => $this->realConversationQuery()->whereIn('status', ['baru', 'open'])->count(),
            'pending' => $this->realConversationQuery()->where('status', 'pending')->count(),
            'resolved' => $this->realConversationQuery()->whereIn('status', ['closed', 'resolved'])->count(),
            'unassigned' => $this->realConversationQuery()->whereNull('assigned_to')->count(),
        ];

        return view('admin.service.omnichannel.index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'search' => $search,
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
            'selectedFilter' => $inboxFilter,
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.omnichannel.create', [
            'message' => null,
            'customers' => $this->customers(),
            'channelOptions' => $this->channelOptions(),
            'directionOptions' => $this->directionOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $message = OmnichannelMessage::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.omnichannel.show', $message)
            ->with('success', 'Omnichannel message berhasil ditambahkan.');
    }

    public function reply(
        Request $request,
        WhatsAppConversation $conversation,
        WhatsAppManager $manager,
        WhatsAppConversationService $conversationService,
    ): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,mp4,mp3'],
        ]);

        $attachment = $request->file('attachment');
        $messageBody = trim((string) ($data['message'] ?? ''));

        if ($attachment instanceof UploadedFile) {
            $media = $this->storeReplyAttachment($attachment, $messageBody);
            $result = $manager->sendMediaMessage(
                $conversation->phone_number,
                Storage::disk('public')->path($media['path']),
                $media['type'],
                [
                    'caption' => $messageBody,
                    'filename' => $media['original_name'],
                    'mime_type' => $media['mime'],
                ],
            );
            $conversationService->recordOutgoingMediaReply($conversation, $media, $result);
        } else {
            $result = $manager->sendMessage($conversation->phone_number, $messageBody);
            $conversationService->recordOutgoingReply($conversation, $messageBody, $result);
        }

        $success = (bool) ($result['success'] ?? false);
        $error = $this->errorMessageFromProviderResult($result);

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with($success ? 'success' : 'error', $success ? 'Balasan WhatsApp berhasil dikirim.' : "Balasan gagal dikirim: {$error}");
    }

    public function assign(WhatsAppConversation $conversation, WhatsAppConversationService $conversationService): RedirectResponse
    {
        $conversationService->assignToAgent($conversation, auth()->user()?->name ?? 'CRM Agent');

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with('success', 'Percakapan berhasil diambil.');
    }

    public function resolve(WhatsAppConversation $conversation, WhatsAppConversationService $conversationService): RedirectResponse
    {
        $conversationService->markResolved($conversation);

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with('success', 'Percakapan ditandai selesai.');
    }

    public function destroyConversation(WhatsAppConversation $conversation): RedirectResponse
    {
        DB::transaction(fn () => $conversation->delete());

        return redirect()
            ->route('admin.service.omnichannel.index')
            ->with('success', 'Conversation WhatsApp berhasil dihapus.');
    }

    public function bulkDestroyConversations(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'conversation_ids' => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['integer', 'exists:whatsapp_conversations,id'],
        ]);

        DB::transaction(function () use ($data): void {
            WhatsAppConversation::query()
                ->whereIn('id', $data['conversation_ids'])
                ->delete();
        });

        return redirect()
            ->route('admin.service.omnichannel.index')
            ->with('success', 'Conversation WhatsApp terpilih berhasil dihapus.');
    }

    public function show(OmnichannelMessage $omnichannel): View
    {
        return view('admin.service.omnichannel.show', [
            'message' => $omnichannel->load('customer:id,name'),
        ]);
    }

    public function edit(OmnichannelMessage $omnichannel): View
    {
        return view('admin.service.omnichannel.edit', [
            'message' => $omnichannel,
            'customers' => $this->customers(),
            'channelOptions' => $this->channelOptions(),
            'directionOptions' => $this->directionOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, OmnichannelMessage $omnichannel): RedirectResponse
    {
        $omnichannel->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.omnichannel.show', $omnichannel)
            ->with('success', 'Omnichannel message berhasil diperbarui.');
    }

    public function destroy(OmnichannelMessage $omnichannel): RedirectResponse
    {
        $omnichannel->delete();

        return redirect()
            ->route('admin.service.omnichannel.index')
            ->with('success', 'Omnichannel message berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'channel' => ['required', Rule::in($this->channelOptions())],
            'direction' => ['required', Rule::in($this->directionOptions())],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_contact' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['whatsapp', 'email', 'livechat', 'facebook', 'instagram', 'telegram'];
    }

    /**
     * @return array<int, string>
     */
    protected function directionOptions(): array
    {
        return ['inbound', 'outbound'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['unread', 'read', 'pending', 'resolved'];
    }

    /**
     * @return array<int, string>
     */
    protected function conversationStatusOptions(): array
    {
        return ['baru', 'open', 'pending', 'closed', 'resolved'];
    }

    protected function realConversationQuery(): Builder
    {
        return WhatsAppConversation::query()
            ->whereHas('messages', fn (Builder $query) => $query->where('direction', 'inbound'));
    }

    protected function markSelectedConversationAsRead(?WhatsAppConversation $conversation): void
    {
        if (! $conversation || $conversation->unread_count <= 0) {
            return;
        }

        $conversation->update(['unread_count' => 0]);
        $conversation->setAttribute('unread_count', 0);
    }

    /**
     * @return array{path:string, original_name:string, mime:?string, size:int, type:string, caption:string, url:string}
     */
    protected function storeReplyAttachment(UploadedFile $attachment, string $caption): array
    {
        $path = $attachment->store('whatsapp-attachments', 'public');

        return [
            'path' => $path,
            'original_name' => $attachment->getClientOriginalName(),
            'mime' => $attachment->getMimeType(),
            'size' => (int) $attachment->getSize(),
            'type' => $this->mediaTypeFromMime((string) $attachment->getMimeType()),
            'caption' => $caption,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    protected function mediaTypeFromMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function errorMessageFromProviderResult(array $result): string
    {
        $raw = $result['raw'] ?? [];

        if (is_array($raw)) {
            $message = $result['reason']
                ?? $raw['reason']
                ?? data_get($raw, 'error.message')
                ?? data_get($raw, 'message.error.message')
                ?? $raw['message']
                ?? null;

            return is_string($message) && trim($message) !== '' ? $message : 'WhatsApp provider failed.';
        }

        return 'WhatsApp provider failed.';
    }

    protected function customers()
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
