<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $legacyMessagesQuery = OmnichannelMessage::query()
            ->with('customer:id,name')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterChannel($channel, $this->channelOptions())
            ->filterStatus($status, $this->statusOptions());

        $messages = (clone $legacyMessagesQuery)
            ->latest('received_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $conversationsQuery = WhatsAppConversation::query()
            ->with([
                'customer:id,name,phone,whatsapp,status',
                'lead:id,name,phone,whatsapp,status,priority,assigned_to',
                'messages' => fn ($query) => $query->latest()->limit(80),
            ])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($inboxFilter === 'belum-diambil', fn ($query) => $query->whereNull('assigned_to'))
            ->when($inboxFilter === 'milik-saya', fn ($query) => $query->where('assigned_to', auth()->user()?->name))
            ->latest('last_message_at')
            ->latest();
        $conversations = $conversationsQuery->get();
        $selectedConversationId = (int) $request->query('conversation', 0);
        $selectedConversation = $selectedConversationId > 0
            ? $conversations->firstWhere('id', $selectedConversationId)
            : $conversations->first();

        $summary = [
            'total' => OmnichannelMessage::query()->count(),
            'unread' => OmnichannelMessage::query()->where('status', 'unread')->count(),
            'pending' => OmnichannelMessage::query()->where('status', 'pending')->count(),
            'resolved' => OmnichannelMessage::query()->where('status', 'resolved')->count(),
            'whatsapp_conversations' => WhatsAppConversation::query()->count(),
            'unassigned' => WhatsAppConversation::query()->whereNull('assigned_to')->count(),
        ];

        return view('admin.service.omnichannel.index', [
            'messages' => $messages,
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

    public function reply(Request $request, WhatsAppConversation $conversation, WhatsAppManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $result = $manager->sendMessage($conversation->phone_number, $data['message']);
        $success = (bool) ($result['success'] ?? false);
        $error = $this->errorMessageFromProviderResult($result);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'lead_id' => $conversation->lead_id,
            'phone' => $conversation->phone_number,
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => $data['message'],
            'provider_message_id' => $result['message_id'] ?? null,
            'provider' => $result['provider'] ?? 'fonnte',
            'status' => $success ? 'sent' : 'failed',
            'sent_at' => now(),
            'failed_at' => $success ? null : now(),
            'error_message' => $success ? null : $error,
        ]);

        $conversation->update([
            'last_message' => $data['message'],
            'last_message_at' => now(),
        ]);

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with($success ? 'success' : 'error', $success ? 'Balasan WhatsApp berhasil dikirim.' : "Balasan gagal dikirim: {$error}");
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
     * @param array<string, mixed> $result
     */
    protected function errorMessageFromProviderResult(array $result): string
    {
        $raw = $result['raw'] ?? [];

        if (is_array($raw)) {
            return (string) ($raw['reason'] ?? $raw['error'] ?? $raw['message'] ?? 'WhatsApp provider failed.');
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
