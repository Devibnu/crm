<?php

namespace App\Support;

use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;

class WhatsAppInboxService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildThreads(): array
    {
        return Tiket::query()
            ->with([
                'pelanggan:id,nama,email,no_hp',
                'assignedUser:id,full_name,email,role',
                'pesan' => fn ($builder) => $builder->oldest(),
            ])
            ->latest()
            ->get()
            ->map(fn (Tiket $tiket) => $this->transformThread($tiket))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timelineEntriesForCustomer(Pelanggan $customer): array
    {
        return Tiket::query()
            ->with([
                'pelanggan:id,nama,email,no_hp',
                'pesan' => fn ($builder) => $builder->oldest(),
            ])
            ->where('pelanggan_id', $customer->id)
            ->latest()
            ->get()
            ->flatMap(function (Tiket $tiket) use ($customer) {
                return $tiket->pesan->map(function (Pesan $pesan) use ($tiket, $customer): array {
                    $isOutgoing = in_array($pesan->channel, ['balasan-internal', 'balasan-pelanggan'], true);

                    return [
                        'id' => sprintf('whatsapp-%s', $pesan->id),
                        'type' => $isOutgoing ? 'whatsapp_sent' : 'whatsapp_received',
                        'title' => $isOutgoing
                            ? sprintf('WhatsApp terkirim ke %s', $customer->nama)
                            : sprintf('WhatsApp masuk dari %s', $customer->nama),
                        'description' => $pesan->isi_pesan,
                        'eventAt' => optional($pesan->created_at)->toIso8601String(),
                        'meta' => [
                            'source' => 'whatsapp',
                            'ticketId' => $tiket->id,
                            'messageId' => $pesan->id,
                            'phone' => $customer->no_hp,
                            'contactName' => $customer->nama,
                            'direction' => $isOutgoing ? 'outgoing' : 'incoming',
                        ],
                        'user' => null,
                    ];
                });
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformThread(Tiket $tiket): array
    {
        $messages = $tiket->pesan->map(fn (Pesan $pesan) => [
            'id' => $pesan->id,
            'sender' => $this->isAgentMessage($pesan) ? 'agent' : 'customer',
            'text' => $pesan->isi_pesan,
            'time' => optional($pesan->created_at)->format('H.i'),
            'createdAt' => optional($pesan->created_at)->toIso8601String(),
        ])->values();

        $firstMessage = $tiket->pesan->first();
        $lastMessage = $tiket->pesan->last();
        $isOutboundThread = $firstMessage ? $this->isAgentMessage($firstMessage) : true;
        $queueStatus = $this->resolveQueueStatus($tiket, $lastMessage);
        $initials = collect(explode(' ', trim((string) $tiket->pelanggan?->nama)))
            ->filter()
            ->take(2)
            ->map(fn (string $segment) => strtoupper(substr($segment, 0, 1)))
            ->implode('');

        return [
            'id' => $tiket->id,
            'ticketId' => $tiket->id,
            'name' => $tiket->pelanggan?->nama ?? sprintf('TIK-%03d', $tiket->id),
            'phone' => $tiket->pelanggan?->no_hp ?? '-',
            'avatarColor' => match ($tiket->prioritas) {
                'tinggi' => 'error',
                'sedang' => 'warning',
                default => 'primary',
            },
            'initials' => $initials !== '' ? $initials : 'WA',
            'lastSnippet' => $lastMessage?->isi_pesan ?? ($tiket->subjek ?: 'Belum ada pesan'),
            'lastTime' => optional($lastMessage?->created_at ?? $tiket->updated_at)->diffForHumans(now(), true),
            'lastActivityAt' => optional($lastMessage?->created_at ?? $tiket->updated_at)->toIso8601String(),
            'unread' => $queueStatus === 'butuh_respons',
            'assignedTo' => $tiket->assignedUser?->email,
            'assignedUserId' => $tiket->assignedUser?->id,
            'status' => $queueStatus,
            'priority' => $tiket->prioritas === 'tinggi' ? 'high' : 'normal',
            'labels' => array_values(array_filter(array_unique([
                $isOutboundThread ? 'outbound' : 'inbound',
                $tiket->kategori,
                $tiket->status === 'selesai' ? 'done' : null,
                $tiket->assigned_user_id ? 'assigned' : 'unassigned',
            ]))),
            'messages' => $messages,
        ];
    }

    private function isAgentMessage(Pesan $pesan): bool
    {
        return in_array($pesan->channel, ['balasan-internal', 'balasan-pelanggan'], true);
    }

    private function resolveQueueStatus(Tiket $tiket, ?Pesan $lastMessage): string
    {
        if ($tiket->status === 'selesai') {
            return 'selesai';
        }

        if (! $lastMessage) {
            return 'menunggu_pelanggan';
        }

        return $this->isAgentMessage($lastMessage)
            ? 'menunggu_pelanggan'
            : 'butuh_respons';
    }
}