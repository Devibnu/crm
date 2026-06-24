<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OmnichannelMessage;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
        $search = $this->selectedSearch($request);
        $channel = $this->selectedChannel($request);
        $status = $this->selectedStatus($request);
        $inboxFilter = $this->selectedInboxFilter($request);

        $conversations = $this->conversationsForRequest($request);
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
            'customerWorkspace' => $this->customerWorkspace($selectedConversation),
            'search' => $search,
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
            'selectedFilter' => $inboxFilter,
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
            'conversationTimeline' => $this->conversationTimeline($selectedConversation),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $conversations = $this->conversationsForRequest($request);
        $selectedConversationId = (int) $request->query('conversation', 0);
        $selectedConversation = $selectedConversationId > 0
            ? $conversations->firstWhere('id', $selectedConversationId)
            : $conversations->first();

        $this->markSelectedConversationAsRead($selectedConversation);

        return response()->json([
            'data' => [
                'conversations' => $conversations
                    ->map(fn (WhatsAppConversation $conversation): array => $this->conversationPayload($conversation, $selectedConversation?->id))
                    ->values(),
                'selected_conversation_id' => $selectedConversation?->id,
                'selected_conversation' => $this->selectedConversationPayload($selectedConversation),
                'messages' => $selectedConversation
                    ? $selectedConversation->messages
                        ->sortBy('created_at')
                        ->map(fn ($message): array => $this->messagePayload($message))
                        ->values()
                    : [],
                'workspace' => $this->workspacePayload($selectedConversation),
                'summary' => [
                    'total' => $this->realConversationQuery()->count(),
                    'open' => $this->realConversationQuery()->whereIn('status', ['baru', 'open'])->count(),
                    'pending' => $this->realConversationQuery()->where('status', 'pending')->count(),
                    'resolved' => $this->realConversationQuery()->whereIn('status', ['closed', 'resolved'])->count(),
                    'unassigned' => $this->realConversationQuery()->whereNull('assigned_to')->count(),
                ],
            ],
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
    ): RedirectResponse|JsonResponse
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

        $message = $success ? 'Balasan WhatsApp berhasil dikirim.' : "Balasan gagal dikirim: {$error}";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'success' => $success,
                'conversation_id' => $conversation->id,
            ], $success ? 200 : 422);
        }

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with($success ? 'success' : 'error', $message);
    }

    public function assign(WhatsAppConversation $conversation, WhatsAppConversationService $conversationService): RedirectResponse
    {
        $conversationService->assignToAgent($conversation, auth()->user()?->name ?? 'CRM Agent');

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with('success', 'Percakapan berhasil diambil.');
    }

    public function updateClassification(WhatsAppConversation $conversation): RedirectResponse
    {
        $data = request()->validate([
            'conversation_type' => ['required', 'string', 'in:sales,support,billing,project,general'],
        ]);

        $conversation->update([
            'tags' => [$data['conversation_type']],
        ]);

        return redirect()
            ->route('admin.service.omnichannel.index', ['conversation' => $conversation->id])
            ->with('success', 'Tipe percakapan berhasil diperbarui.');
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

    protected function selectedSearch(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    protected function selectedChannel(Request $request): string
    {
        return trim((string) $request->query('channel', ''));
    }

    protected function selectedStatus(Request $request): string
    {
        return trim((string) $request->query('status', ''));
    }

    protected function selectedInboxFilter(Request $request): string
    {
        return trim((string) $request->query('filter', 'semua'));
    }

    protected function conversationsForRequest(Request $request): \Illuminate\Support\Collection
    {
        $search = $this->selectedSearch($request);
        $status = $this->selectedStatus($request);
        $inboxFilter = $this->selectedInboxFilter($request);

        return WhatsAppConversation::query()
            ->with([
                'customer:id,name,phone,whatsapp,status',
                'lead:id,name,phone,whatsapp,status,priority,assigned_to',
                'messages' => fn ($query) => $query->with([
                    'lead:id,name',
                    'ticket:id,ticket_number,subject',
                ])->latest()->limit(80),
            ])
            ->whereHas('messages', fn (Builder $query) => $query->where('direction', 'inbound'))
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($status !== '' && in_array($status, $this->conversationStatusOptions(), true), fn ($query) => $query->where('status', $status))
            ->when($inboxFilter === 'belum-diambil', fn ($query) => $query->whereNull('assigned_to'))
            ->when($inboxFilter === 'milik-saya', fn ($query) => $query->where('assigned_to', auth()->user()?->name))
            ->when($inboxFilter === 'open', fn ($query) => $query->whereIn('status', ['baru', 'open', 'pending']))
            ->when($inboxFilter === 'resolved', fn ($query) => $query->whereIn('status', ['closed', 'resolved']))
            ->latest('last_message_at')
            ->latest()
            ->get();
    }

    protected function conversationPayload(WhatsAppConversation $conversation, ?int $selectedConversationId): array
    {
        $name = $this->conversationDisplayName($conversation);
        $conversationStatus = in_array($conversation->status, ['closed', 'resolved'], true) ? 'Resolved' : 'Open';

        return [
            'id' => $conversation->id,
            'name' => $name,
            'initials' => $this->initials($name),
            'phone_number' => $conversation->phone_number,
            'last_message' => str($conversation->last_message ?: 'Belum ada pesan')->limit(42)->toString(),
            'last_message_at' => $conversation->last_message_at?->diffForHumans() ?: '-',
            'unread_count' => (int) $conversation->unread_count,
            'assigned' => filled($conversation->assigned_to),
            'assigned_to' => $conversation->assigned_to,
            'status_label' => $conversationStatus,
            'status_class' => strtolower($conversationStatus),
            'is_active' => $selectedConversationId === $conversation->id,
            'href' => route('admin.service.omnichannel.index', [
                'q' => request('q'),
                'filter' => request('filter', 'semua'),
                'status' => request('status'),
                'conversation' => $conversation->id,
            ]),
        ];
    }

    protected function selectedConversationPayload(?WhatsAppConversation $conversation): ?array
    {
        if (! $conversation) {
            return null;
        }

        $name = $this->conversationDisplayName($conversation);
        $activeProvider = strtolower((string) ($conversation->messages->firstWhere('provider')?->provider ?? 'meta'));

        return [
            'id' => $conversation->id,
            'name' => $name,
            'initials' => strtoupper(mb_substr($name, 0, 2)),
            'phone_number' => $conversation->phone_number,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'provider_label' => $activeProvider === 'meta' ? 'Meta Cloud API' : 'Fonnte',
            'provider_class' => $activeProvider === 'meta' ? 'meta' : 'fonnte',
            'reply_url' => route('admin.service.omnichannel.reply', $conversation),
            'assign_url' => route('admin.service.omnichannel.assign', $conversation),
            'notes_url' => auth()->user()?->can('omnichannel_notes.view')
                ? route('admin.service.omnichannel.notes.index', $conversation)
                : null,
            'notes_store_url' => auth()->user()?->can('omnichannel_notes.create')
                ? route('admin.service.omnichannel.notes.store', $conversation)
                : null,
        ];
    }

    protected function messagePayload($message): array
    {
        $messageTime = $message->received_at ?? $message->sent_at ?? $message->created_at;
        $dateLabel = $messageTime?->isToday() ? 'Hari Ini' : ($messageTime?->isYesterday() ? 'Kemarin' : $messageTime?->format('d M Y'));
        $mediaUrl = $message->media_url ?: ($message->media_path ? Storage::disk('public')->url($message->media_path) : null);
        $mediaMime = (string) $message->media_mime;

        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'message' => (string) $message->message,
            'status' => ucfirst((string) $message->status),
            'time' => $messageTime?->format('H:i') ?: '',
            'date_label' => $dateLabel,
            'activity_label' => $message->direction === 'inbound' ? 'Customer replied' : 'Agent replied',
            'activity_time' => $messageTime?->diffForHumans() ?: '',
            'media' => $mediaUrl ? [
                'url' => $mediaUrl,
                'name' => $message->media_original_name ?: basename((string) $message->media_path),
                'mime' => $mediaMime,
                'size_label' => $message->media_size ? number_format($message->media_size / 1024, 1).' KB' : ($mediaMime ?: 'attachment'),
                'is_image' => str_starts_with($mediaMime, 'image/'),
                'is_video' => str_starts_with($mediaMime, 'video/'),
            ] : null,
        ];
    }

    protected function workspacePayload(?WhatsAppConversation $conversation): array
    {
        $workspace = $this->customerWorkspace($conversation);
        $customer = $workspace['customer'];
        $lead = $workspace['lead'];
        $activeTicket = $workspace['activeTicket'];
        $hasLead = filled($lead);
        $hasTicket = filled($activeTicket);
        $isClosed = in_array($conversation?->status, ['closed', 'resolved'], true);
        $currentStage = $isClosed ? 'Resolved' : ($hasLead ? 'Lead Created' : ($hasTicket ? 'Need Support Ticket' : 'Need Follow Up'));
        $timelineEvents = collect($this->conversationTimeline($conversation))
            ->sortByDesc(fn ($event) => $event['time']?->timestamp ?? 0)
            ->values();

        return [
            'contact' => [
                'name' => $conversation?->contact_name ?: $customer?->name ?: $lead?->name ?: 'Customer Workspace',
                'initials' => $conversation ? strtoupper(mb_substr($conversation->contact_name ?: $conversation->phone_number, 0, 2)) : 'WA',
                'phone_number' => $conversation?->phone_number ?: 'Pilih percakapan untuk melihat detail.',
                'lifecycle_label' => $customer ? 'Customer' : ($lead ? 'Lead / Prospect' : 'Unknown Contact'),
                'lifecycle_class' => $customer ? 'status-active' : ($lead ? 'lead-temperature-warm' : 'status-open'),
                'conversation_type' => collect((array) ($conversation?->tags ?? []))->first() ?: 'general',
                'classification_url' => $conversation ? route('admin.service.omnichannel.classification', $conversation) : null,
                'status' => ucfirst($conversation?->status ?? 'open'),
                'status_class' => 'status-'.($conversation?->status ?? 'open'),
                'customer_url' => $customer ? route('admin.customers.show', $customer) : null,
                'customer_name' => $customer?->name,
                'lead_url' => $lead ? route('admin.sales.leads.show', $lead) : null,
                'lead_name' => $lead?->name,
            ],
            'crm' => [
                'current_stage' => $currentStage,
                'current_stage_class' => str($currentStage)->lower()->replace(' ', '-')->toString(),
                'assigned_to' => $conversation?->assigned_to,
                'assign_url' => $conversation ? route('admin.service.omnichannel.assign', $conversation) : null,
                'resolve_url' => $conversation ? route('admin.service.omnichannel.resolve', $conversation) : null,
                'events' => $timelineEvents->map(fn (array $event): array => [
                    'label' => $event['label'],
                    'description' => $event['description'],
                    'time' => $event['time']?->format('d M Y H:i'),
                ]),
                'tickets' => $workspace['tickets']->map(fn (Ticket $ticket): array => [
                    'label' => $ticket->ticket_number,
                    'description' => str($ticket->subject)->limit(44)->toString(),
                    'url' => route('admin.service.tickets.show', $ticket),
                ]),
                'opportunities' => $workspace['opportunities']->map(fn (Opportunity $opportunity): array => [
                    'label' => $opportunity->title,
                    'description' => ucfirst($opportunity->status).' · '.number_format((float) $opportunity->estimated_value, 0, ',', '.'),
                    'url' => route('admin.sales.opportunities.show', $opportunity),
                ]),
                'quotations' => $workspace['quotations']->map(fn (Quotation $quotation): array => [
                    'label' => $quotation->quote_number,
                    'description' => str($quotation->title)->limit(44)->toString().' · '.ucfirst($quotation->status),
                    'url' => route('admin.sales.deals.show', $quotation),
                ]),
            ],
        ];
    }

    protected function conversationDisplayName(WhatsAppConversation $conversation): string
    {
        return $conversation->contact_name ?: $conversation->customer?->name ?: $conversation->lead?->name ?: $conversation->phone_number;
    }

    protected function initials(string $name): string
    {
        return collect(explode(' ', $name))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('') ?: 'W';
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

    protected function conversationTimeline(?WhatsAppConversation $conversation): \Illuminate\Support\Collection
    {
        if (! $conversation) {
            return collect();
        }

        $events = collect();

        foreach ($conversation->messages->sortBy('created_at') as $message) {
            $time = $message->received_at ?? $message->sent_at ?? $message->created_at;
            $body = trim((string) $message->message);

            if ($message->direction === 'outbound') {
                $events->push([
                    'time' => $time,
                    'label' => str_starts_with($body, 'Template:') ? 'Broadcast Sent' : 'Agent outbound reply',
                    'description' => $body !== '' ? str($body)->limit(90)->toString() : 'Outbound WhatsApp message',
                ]);
            } else {
                $events->push([
                    'time' => $time,
                    'label' => 'Customer Reply',
                    'description' => $body !== '' ? str($body)->limit(90)->toString() : 'Inbound WhatsApp message',
                ]);
            }

            if ($message->lead_id) {
                $events->push([
                    'time' => $message->updated_at ?? $time,
                    'label' => 'Converted To Lead',
                    'description' => $message->lead?->name ? "Lead: {$message->lead->name}" : 'Lead linked from WhatsApp reply',
                ]);
            }

            if ($message->ticket_id) {
                $events->push([
                    'time' => $message->ticket?->created_at ?? $message->updated_at ?? $time,
                    'label' => 'Ticket Created',
                    'description' => $message->ticket
                        ? "{$message->ticket->ticket_number} - {$message->ticket->subject}"
                        : 'Ticket linked from WhatsApp reply',
                ]);
            }
        }

        if ($conversation->assigned_to) {
            $events->push([
                'time' => $conversation->taken_at ?? $conversation->updated_at,
                'label' => 'Conversation Assigned',
                'description' => "Ditangani oleh {$conversation->assigned_to}",
            ]);
        }

        if ($conversation->closed_at || in_array($conversation->status, ['closed', 'resolved'], true)) {
            $events->push([
                'time' => $conversation->closed_at ?? $conversation->updated_at,
                'label' => 'Conversation Resolved',
                'description' => 'Percakapan ditandai selesai',
            ]);
        }

        return $events
            ->filter(fn (array $event) => $event['time'])
            ->sortByDesc(fn (array $event) => $event['time']->timestamp)
            ->values();
    }

    protected function customerWorkspace(?WhatsAppConversation $conversation): array
    {
        if (! $conversation) {
            return [
                'customer' => null,
                'lead' => null,
                'tickets' => collect(),
                'activeTicket' => null,
                'opportunities' => collect(),
                'quotations' => collect(),
            ];
        }

        $messageIds = $conversation->messages->pluck('id')->filter()->values();
        $customer = $conversation->customer;
        $lead = $conversation->lead ?: $conversation->messages->first(fn ($message) => $message->lead)?->lead;
        $customerId = $customer?->id;
        $leadId = $lead?->id;

        $tickets = ($customerId || $leadId || $messageIds->isNotEmpty())
            ? Ticket::query()
                ->with(['customer:id,name', 'lead:id,name'])
                ->where(function (Builder $query) use ($customerId, $leadId, $messageIds) {
                    $query
                        ->when($customerId, fn ($inner) => $inner->orWhere('customer_id', $customerId))
                        ->when($leadId, fn ($inner) => $inner->orWhere('lead_id', $leadId))
                        ->when($messageIds->isNotEmpty(), fn ($inner) => $inner->orWhereIn('whatsapp_message_id', $messageIds));
                })
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $opportunities = ($customerId || $leadId)
            ? Opportunity::query()
                ->where(function (Builder $query) use ($customerId, $leadId) {
                    $query
                        ->when($customerId, fn ($inner) => $inner->orWhere('customer_id', $customerId))
                        ->when($leadId, fn ($inner) => $inner->orWhere('lead_id', $leadId));
                })
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $opportunityIds = $opportunities->pluck('id');
        $quotations = ($customerId || $opportunityIds->isNotEmpty())
            ? Quotation::query()
                ->with('opportunity:id,title')
                ->where(function (Builder $query) use ($customerId, $opportunityIds) {
                    $query
                        ->when($customerId, fn ($inner) => $inner->orWhere('customer_id', $customerId))
                        ->when($opportunityIds->isNotEmpty(), fn ($inner) => $inner->orWhereIn('opportunity_id', $opportunityIds));
                })
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return [
            'customer' => $customer,
            'lead' => $lead,
            'tickets' => $tickets,
            'activeTicket' => $tickets->first(),
            'opportunities' => $opportunities,
            'quotations' => $quotations,
        ];
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
