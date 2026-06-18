<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Ticket;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\LeadQualificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppReplyInboxController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $campaign = trim((string) $request->query('campaign', ''));

        $broadcastReplies = WhatsAppBroadcastReply::query()
            ->with(['broadcast:id,name', 'lead:id,name,whatsapp,phone', 'ticket:id,ticket_number,subject'])
            ->search($search)
            ->when($status !== '' && in_array($status, $this->statusOptions(), true), fn ($query) => $query->where('status', $status))
            ->when($campaign !== '', fn ($query) => $query->whereHas('broadcast', fn ($broadcastQuery) => $broadcastQuery->where('name', $campaign)))
            ->latest('received_at')
            ->get()
            ->map(function (WhatsAppBroadcastReply $reply) {
                $classification = $reply->resolvedClassification();
                $lead = $reply->lead ?: $this->findLeadByPhone($reply->phone_number);
                $ticket = $reply->ticket ?: $this->findTicketForSource('whatsapp_broadcast_reply', $reply->id);

                return $this->withWorkflowPresentation([
                    'id' => $reply->id,
                    'sender_name' => $reply->sender_name,
                    'phone_number' => $reply->phone_number,
                    'message' => $reply->message,
                    'related_campaign' => $reply->broadcast?->name ?: '-',
                    'status' => $reply->status,
                    'reply_type' => $classification['reply_type'],
                    'sentiment' => $classification['sentiment'],
                    'action_status' => $classification['action_status'],
                    'received_at' => $reply->received_at,
                    'source' => 'broadcast',
                    'source_label' => 'Broadcast',
                    'convert_to_lead_url' => route('admin.marketing.whatsapp-replies.convert-to-lead', $reply),
                    'create_ticket_url' => route('admin.marketing.whatsapp-replies.create-ticket', $reply),
                    'send_to_omnichannel_url' => route('admin.marketing.whatsapp-replies.send-to-omnichannel', $reply),
                    'mark_closed_url' => route('admin.marketing.whatsapp-replies.mark-closed', $reply),
                    'open_omnichannel_url' => null,
                    'lead_exists' => (bool) $lead,
                    'lead_url' => $lead ? route('admin.sales.leads.show', $lead) : null,
                    'ticket_exists' => (bool) $ticket,
                    'ticket_url' => $ticket ? route('admin.service.tickets.show', $ticket) : null,
                ]);
            });

        $omnichannelReplies = WhatsAppMessage::query()
            ->with([
                'conversation:id,customer_id,lead_id,contact_name,phone_number',
                'conversation.customer:id,name,whatsapp,phone',
                'conversation.lead:id,name,whatsapp,phone',
                'customer:id,name,whatsapp,phone',
                'lead:id,name,whatsapp,phone',
                'ticket:id,ticket_number,subject',
            ])
            ->whereRaw('LOWER(TRIM(direction)) = ?', ['inbound'])
            ->whereRaw('LOWER(TRIM(provider)) = ?', ['meta'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('phone', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('conversation', function ($conversationQuery) use ($search) {
                            $conversationQuery
                                ->where('contact_name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('lead', fn ($leadQuery) => $leadQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '' && in_array($status, $this->statusOptions(), true), fn ($query) => $query->where('status', $status))
            ->latest('received_at')
            ->latest('created_at')
            ->get()
            ->map(function (WhatsAppMessage $message) {
                $classification = WhatsAppBroadcastReply::classifyMessage((string) $message->message);
                $senderName = $message->conversation?->contact_name
                    ?: $message->customer?->name
                    ?: $message->conversation?->customer?->name
                    ?: $message->lead?->name
                    ?: $message->conversation?->lead?->name
                    ?: $message->phone
                    ?: $message->conversation?->phone_number
                    ?: '-';
                $phone = $message->conversation?->phone_number ?: $message->phone ?: '-';
                $lead = $message->lead
                    ?: $message->conversation?->lead
                    ?: $this->findLeadByPhone($phone);
                $ticket = $message->ticket ?: $this->findTicketForSource('whatsapp_message', $message->id);

                return $this->withWorkflowPresentation([
                    'id' => $message->id,
                    'sender_name' => $senderName,
                    'phone_number' => $phone,
                    'message' => $message->message,
                    'related_campaign' => 'Omnichannel WhatsApp',
                    'status' => $message->status,
                    'reply_type' => $classification['reply_type'],
                    'sentiment' => $classification['sentiment'],
                    'action_status' => $classification['action_status'],
                    'received_at' => $message->received_at ?? $message->created_at,
                    'source' => 'omnichannel',
                    'source_label' => 'Omnichannel',
                    'convert_to_lead_url' => route('admin.marketing.whatsapp-replies.messages.convert-to-lead', $message),
                    'create_ticket_url' => route('admin.marketing.whatsapp-replies.messages.create-ticket', $message),
                    'send_to_omnichannel_url' => null,
                    'mark_closed_url' => route('admin.marketing.whatsapp-replies.messages.mark-closed', $message),
                    'open_omnichannel_url' => url('/admin/service/omnichannel?conversation='.$message->whatsapp_conversation_id),
                    'lead_exists' => (bool) $lead,
                    'lead_url' => $lead ? route('admin.sales.leads.show', $lead) : null,
                    'ticket_exists' => (bool) $ticket,
                    'ticket_url' => $ticket ? route('admin.service.tickets.show', $ticket) : null,
                ]);
            });

        $mergedReplies = $this->mergeAndSortReplies($broadcastReplies, $omnichannelReplies)
            ->when($campaign !== '', function (Collection $collection) use ($campaign) {
                return $collection->filter(fn (array $row) => $row['source'] !== 'broadcast' || $row['related_campaign'] === $campaign);
            })
            ->values();

        return view('admin.marketing.whatsapp-replies.index', [
            'title' => 'WhatsApp Reply Inbox',
            'description' => 'Pantau balasan WhatsApp dari customer atau lead setelah broadcast dikirim.',
            'search' => $search,
            'selectedStatus' => $status,
            'selectedCampaign' => $campaign,
            'statusOptions' => $this->statusOptions(),
            'campaignOptions' => array_values(array_unique(array_merge(
                WhatsAppBroadcast::query()->orderBy('name')->pluck('name')->all(),
                ['Omnichannel WhatsApp'],
            ))),
            'summaryCards' => [
                ['label' => 'Total Replies', 'value' => number_format($mergedReplies->count()), 'hint' => 'Gabungan inbox broadcast + omnichannel'],
                ['label' => 'Lead Replies', 'value' => number_format($mergedReplies->where('reply_type', 'lead')->count()), 'hint' => 'Berminat atau minta penawaran'],
                ['label' => 'Support Replies', 'value' => number_format($mergedReplies->where('reply_type', 'support')->count()), 'hint' => 'Komplain, invoice, tiket, masalah'],
                ['label' => 'Unsubscribe Replies', 'value' => number_format($mergedReplies->where('reply_type', 'unsubscribe')->count()), 'hint' => 'Stop atau opt-out'],
                ['label' => 'Converted To Lead', 'value' => number_format($mergedReplies->where('action_status', 'follow_up_sales')->count()), 'hint' => 'Sudah dibuat ke Lead Management'],
                ['label' => 'Sent To Omnichannel', 'value' => number_format($mergedReplies->where('action_status', 'send_to_omnichannel')->count()), 'hint' => 'Dikirim ke Omnichannel Inbox'],
            ],
            'replyRows' => $mergedReplies,
        ]);
    }

    public function convertToLead(WhatsAppBroadcastReply $reply): RedirectResponse
    {
        $lead = $this->createOrUpdateLeadFromReplyData(
            $reply->sender_name,
            $reply->phone_number,
            $reply->message,
            $reply->received_at,
            isBroadcastReply: true,
            sourceCampaign: $reply->broadcast?->name,
        );

        $reply->update([
            'lead_id' => $lead->id,
            'reply_type' => 'lead',
            'sentiment' => $reply->sentiment ?: 'positive',
            'action_status' => 'follow_up_sales',
            'status' => 'read',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', "Reply berhasil dikonversi menjadi lead {$lead->name}.");
    }

    public function convertMessageToLead(WhatsAppMessage $message): RedirectResponse
    {
        $message->loadMissing(['conversation:id,contact_name,phone_number']);

        $phone = $message->phone ?: $message->conversation?->phone_number;
        $senderName = $message->conversation?->contact_name ?: $phone;
        $lead = $this->createOrUpdateLeadFromReplyData(
            $senderName,
            $phone,
            $message->message,
            $message->received_at ?? $message->sent_at ?? $message->created_at,
            sourceConversationId: $message->whatsapp_conversation_id,
        );

        $message->update([
            'lead_id' => $message->lead_id ?: $lead->id,
            'status' => 'read',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', "Pesan Omnichannel berhasil dikonversi menjadi lead {$lead->name}.");
    }

    public function createTicketFromReply(WhatsAppBroadcastReply $reply): RedirectResponse
    {
        $ticket = $this->createOrFindTicketFromReplyData(
            sourceType: 'whatsapp_broadcast_reply',
            sourceId: $reply->id,
            message: $reply->message,
            senderName: $reply->sender_name,
            phone: $reply->phone_number,
            customerId: $this->findCustomerByPhone($reply->phone_number)?->id,
            leadId: ($reply->lead ?: $this->findLeadByPhone($reply->phone_number))?->id,
            campaign: $reply->broadcast?->name,
            broadcastReplyId: $reply->id,
        );

        $reply->update([
            'ticket_id' => $ticket->id,
            'action_status' => 'send_to_omnichannel',
            'status' => 'read',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', "Ticket {$ticket->ticket_number} siap ditangani.");
    }

    public function createTicketFromMessage(WhatsAppMessage $message): RedirectResponse
    {
        $message->loadMissing([
            'conversation:id,customer_id,lead_id,contact_name,phone_number',
            'conversation.customer:id,name,whatsapp,phone',
            'conversation.lead:id,name,whatsapp,phone',
            'customer:id,name,whatsapp,phone',
            'lead:id,name,whatsapp,phone',
        ]);

        $phone = $message->conversation?->phone_number ?: $message->phone;
        $lead = $message->lead ?: $message->conversation?->lead ?: $this->findLeadByPhone($phone);
        $ticket = $this->createOrFindTicketFromReplyData(
            sourceType: 'whatsapp_message',
            sourceId: $message->id,
            message: $message->message,
            senderName: $message->conversation?->contact_name ?: $message->customer?->name ?: $lead?->name,
            phone: $phone,
            customerId: $message->customer_id ?: $message->conversation?->customer_id,
            leadId: $lead?->id,
            campaign: 'Omnichannel WhatsApp',
            whatsappMessageId: $message->id,
        );

        $message->update([
            'ticket_id' => $ticket->id,
            'status' => 'read',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', "Ticket {$ticket->ticket_number} siap ditangani.");
    }

    public function sendToOmnichannel(WhatsAppBroadcastReply $reply): RedirectResponse
    {
        $conversation = WhatsAppConversation::query()->updateOrCreate(
            ['phone_number' => $reply->phone_number],
            [
                'contact_name' => $reply->sender_name ?: $reply->phone_number,
                'channel' => 'whatsapp',
                'last_message' => $reply->message,
                'last_message_at' => $reply->received_at ?? now(),
                'status' => 'open',
                'priority' => $reply->reply_type === 'support' ? 'high' : 'medium',
                'tags' => ['reply-inbox', $reply->reply_type ?: 'general'],
                'notes' => 'Created from WhatsApp Reply Inbox.',
            ],
        );

        $conversation->increment('unread_count');

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => $reply->phone_number,
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => $reply->message,
            'status' => 'delivered',
            'provider' => 'reply_inbox',
            'broadcast_id' => $reply->whatsapp_broadcast_id,
            'sent_at' => $reply->received_at ?? now(),
            'received_at' => $reply->received_at ?? now(),
        ]);

        $reply->update([
            'reply_type' => $reply->reply_type ?: 'support',
            'sentiment' => $reply->sentiment ?: 'neutral',
            'action_status' => 'send_to_omnichannel',
            'status' => 'read',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', 'Reply berhasil dikirim ke Omnichannel Inbox.');
    }

    public function markClosed(WhatsAppBroadcastReply $reply): RedirectResponse
    {
        $reply->update([
            'action_status' => 'closed',
            'status' => 'resolved',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', 'Reply berhasil ditandai selesai.');
    }

    public function markMessageClosed(WhatsAppMessage $message): RedirectResponse
    {
        $message->update(['status' => 'read']);

        return redirect()
            ->route('admin.marketing.whatsapp-replies.index')
            ->with('success', 'Pesan Omnichannel berhasil ditandai selesai.');
    }

    protected function mergeAndSortReplies(Collection $broadcastReplies, Collection $omnichannelReplies): Collection
    {
        $broadcastKeys = $broadcastReplies
            ->map(fn (array $row) => $this->replyDedupKey($row))
            ->filter()
            ->flip();

        return $broadcastReplies
            ->concat($omnichannelReplies->reject(fn (array $row) => $broadcastKeys->has($this->replyDedupKey($row))))
            ->sortByDesc(fn (array $row) => $row['received_at']?->timestamp ?? 0);
    }

    protected function replyDedupKey(array $row): string
    {
        return implode('|', [
            trim((string) $row['phone_number']),
            trim((string) $row['message']),
            $row['received_at']?->timestamp ?? '',
        ]);
    }

    protected function createOrUpdateLeadFromReplyData(
        ?string $senderName,
        ?string $phone,
        string $message,
        mixed $receivedAt,
        bool $isBroadcastReply = false,
        ?string $sourceCampaign = null,
        ?int $sourceConversationId = null,
    ): Lead {
        $phone = trim((string) $phone);
        $lead = Lead::query()->firstOrCreate(
            ['whatsapp' => $phone],
            [
                'name' => $senderName ?: "WhatsApp Lead {$phone}",
                'phone' => $phone,
                'source' => 'whatsapp_reply_inbox',
                'lead_source' => 'whatsapp_reply_inbox',
                'status' => 'new',
                'priority' => 'medium',
                'last_whatsapp_message' => $message,
                'last_whatsapp_at' => $receivedAt ?? now(),
                'source_campaign' => $sourceCampaign,
                'source_whatsapp_conversation_id' => $sourceConversationId,
                'notes' => 'Converted from WhatsApp Reply Inbox.',
            ],
        );

        $lead->update([
            'last_whatsapp_message' => $message,
            'last_whatsapp_at' => $receivedAt ?? now(),
            'source_campaign' => $sourceCampaign ?: $lead->source_campaign,
            'source_whatsapp_conversation_id' => $sourceConversationId ?: $lead->source_whatsapp_conversation_id,
        ]);

        return app(LeadQualificationService::class)->qualifyWhatsAppLead(
            lead: $lead->fresh(),
            message: $message,
            isBroadcastReply: $isBroadcastReply,
            sourceCampaign: $sourceCampaign,
            sourceConversationId: $sourceConversationId,
        );
    }

    protected function createOrFindTicketFromReplyData(
        string $sourceType,
        int $sourceId,
        string $message,
        ?string $senderName,
        ?string $phone,
        ?int $customerId = null,
        ?int $leadId = null,
        ?string $campaign = null,
        ?int $whatsappMessageId = null,
        ?int $broadcastReplyId = null,
    ): Ticket {
        $existing = $this->findTicketForSource($sourceType, $sourceId, $whatsappMessageId, $broadcastReplyId);

        if ($existing) {
            return $existing;
        }

        return Ticket::create([
            'ticket_number' => $this->generateTicketNumber(),
            'customer_id' => $customerId,
            'lead_id' => $leadId,
            'whatsapp_message_id' => $whatsappMessageId,
            'whatsapp_broadcast_reply_id' => $broadcastReplyId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'subject' => Str::limit(trim($message) ?: 'WhatsApp support reply', 80),
            'description' => implode("\n", array_filter([
                $message,
                '',
                'Source: WhatsApp Reply Inbox',
                "Source Type: {$sourceType}",
                "Source ID: {$sourceId}",
                $senderName ? "Sender: {$senderName}" : null,
                $phone ? "Phone: {$phone}" : null,
                $campaign ? "Campaign: {$campaign}" : null,
            ])),
            'priority' => 'medium',
            'status' => 'open',
            'channel' => 'whatsapp',
        ]);
    }

    protected function findTicketForSource(string $sourceType, int $sourceId, ?int $whatsappMessageId = null, ?int $broadcastReplyId = null): ?Ticket
    {
        return Ticket::query()
            ->when($whatsappMessageId, fn ($query) => $query->orWhere('whatsapp_message_id', $whatsappMessageId))
            ->when($broadcastReplyId, fn ($query) => $query->orWhere('whatsapp_broadcast_reply_id', $broadcastReplyId))
            ->orWhere(function ($query) use ($sourceType, $sourceId) {
                $query->where('source_type', $sourceType)->where('source_id', $sourceId);
            })
            ->first();
    }

    protected function findLeadByPhone(?string $phone): ?Lead
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return Lead::query()
            ->where('whatsapp', $phone)
            ->orWhere('phone', $phone)
            ->first();
    }

    protected function findCustomerByPhone(?string $phone): ?Customer
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return Customer::query()
            ->where('whatsapp', $phone)
            ->orWhere('phone', $phone)
            ->first();
    }

    protected function withWorkflowPresentation(array $row): array
    {
        $row['workflow_badge_label'] = match ($row['reply_type']) {
            'lead' => 'Lead / Hot',
            'support' => 'Support',
            'unsubscribe' => 'Opt Out',
            default => 'General',
        };
        $row['workflow_badge_class'] = match ($row['reply_type']) {
            'lead' => 'status-new',
            'support' => 'status-pending',
            'unsubscribe' => 'status-archived',
            default => 'status-read',
        };

        return $row;
    }

    protected function generateTicketNumber(): string
    {
        do {
            $number = 'TCK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['unread', 'read', 'pending', 'resolved', 'archived'];
    }
}
