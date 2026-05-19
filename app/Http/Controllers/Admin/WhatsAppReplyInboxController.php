<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastReply;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WhatsAppReplyInboxController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $campaign = trim((string) $request->query('campaign', ''));

        $broadcastReplies = WhatsAppBroadcastReply::query()
            ->with('broadcast:id,name')
            ->search($search)
            ->when($status !== '' && in_array($status, $this->statusOptions(), true), fn ($query) => $query->where('status', $status))
            ->when($campaign !== '', fn ($query) => $query->whereHas('broadcast', fn ($broadcastQuery) => $broadcastQuery->where('name', $campaign)))
            ->latest('received_at')
            ->get()
            ->map(fn (WhatsAppBroadcastReply $reply) => [
                'sender_name' => $reply->sender_name,
                'phone_number' => $reply->phone_number,
                'message' => $reply->message,
                'related_campaign' => $reply->broadcast?->name ?: '-',
                'uses_default_campaign_label' => false,
                'status' => $reply->status,
                'received_at' => $reply->received_at,
                'source' => 'broadcast',
            ]);

        $omnichannelReplies = OmnichannelMessage::query()
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($status !== '' && in_array($status, $this->statusOptions(), true), fn ($query) => $query->where('status', $status))
            ->latest('received_at')
            ->get()
            ->map(fn (OmnichannelMessage $message) => [
                'sender_name' => $message->sender_name ?: '-',
                'phone_number' => $message->sender_contact ?: '-',
                'message' => $message->message,
                'related_campaign' => $campaign === '' ? 'Omnichannel WhatsApp' : '-',
                'uses_default_campaign_label' => $campaign === '',
                'status' => $message->status,
                'received_at' => $message->received_at,
                'source' => 'omnichannel',
            ]);

        $mergedReplies = $this->mergeAndSortReplies($broadcastReplies, $omnichannelReplies)
            ->when($campaign !== '', function (Collection $collection) use ($campaign) {
                return $collection->filter(fn (array $row) => $row['related_campaign'] === $campaign);
            })
            ->values();

        return view('admin.marketing.whatsapp-replies.index', [
            'title' => [
                'en' => 'WhatsApp Reply Inbox',
                'id' => 'Inbox Balasan WhatsApp',
            ],
            'description' => [
                'en' => 'Monitor WhatsApp replies from customers or leads after broadcasts are sent.',
                'id' => 'Pantau balasan WhatsApp dari customer atau lead setelah broadcast dikirim.',
            ],
            'search' => $search,
            'selectedStatus' => $status,
            'selectedCampaign' => $campaign,
            'statusOptions' => $this->statusOptions(),
            'campaignOptions' => WhatsAppBroadcast::query()->orderBy('name')->pluck('name')->all(),
            'summaryCards' => [
                [
                    'label_en' => 'Total Replies',
                    'label_id' => 'Total Balasan',
                    'value' => number_format($mergedReplies->count()),
                    'hint_en' => 'Combined broadcast and omnichannel inbox',
                    'hint_id' => 'Gabungan inbox broadcast dan omnichannel',
                ],
                [
                    'label_en' => 'Unread',
                    'label_id' => 'Belum Dibaca',
                    'value' => number_format($mergedReplies->where('status', 'unread')->count()),
                    'hint_en' => 'Needs follow-up',
                    'hint_id' => 'Perlu ditindaklanjuti',
                ],
                [
                    'label_en' => 'Resolved',
                    'label_id' => 'Selesai Ditangani',
                    'value' => number_format($mergedReplies->where('status', 'resolved')->count()),
                    'hint_en' => 'Already handled',
                    'hint_id' => 'Sudah ditangani',
                ],
                [
                    'label_en' => 'Broadcast Source',
                    'label_id' => 'Sumber Broadcast',
                    'value' => number_format($mergedReplies->where('source', 'broadcast')->count()),
                    'hint_en' => 'From whatsapp_broadcast_replies',
                    'hint_id' => 'Dari whatsapp_broadcast_replies',
                ],
            ],
            'replyRows' => $mergedReplies,
        ]);
    }

    protected function mergeAndSortReplies(Collection $broadcastReplies, Collection $omnichannelReplies): Collection
    {
        return $broadcastReplies
            ->concat($omnichannelReplies)
            ->sortByDesc(fn (array $row) => $row['received_at']?->timestamp ?? 0);
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['unread', 'read', 'pending', 'resolved', 'archived'];
    }
}
