<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppMessage;

class LeadQualificationService
{
    /**
     * @return array{score:int, temperature:string, breakdown:array<int, array{label:string, points:int}>}
     */
    public function scoreForWhatsApp(
        Lead $lead,
        ?string $message = null,
        bool $isBroadcastReply = false,
    ): array {
        $breakdown = [];
        $score = 0;

        if ($isBroadcastReply) {
            $score += 5;
            $breakdown[] = ['label' => 'Reply Broadcast', 'points' => 5];
        }

        foreach ($this->keywordScores() as $keyword => $points) {
            if ($this->containsKeyword((string) $message, $keyword)) {
                $score += $points;
                $breakdown[] = ['label' => "Keyword: {$keyword}", 'points' => $points];
            }
        }

        $replyCount = $this->replyCountForLead($lead);
        if ($replyCount > 3) {
            $score += 10;
            $breakdown[] = ['label' => 'Balas lebih dari 3 kali', 'points' => 10];
        }

        return [
            'score' => $score,
            'temperature' => $this->temperatureForScore($score),
            'breakdown' => $breakdown,
        ];
    }

    public function qualifyWhatsAppLead(
        Lead $lead,
        ?string $message = null,
        bool $isBroadcastReply = false,
        ?string $sourceCampaign = null,
        ?int $sourceConversationId = null,
    ): Lead {
        $result = $this->scoreForWhatsApp($lead, $message, $isBroadcastReply);

        $lead->forceFill([
            'lead_score' => $result['score'],
            'lead_temperature' => $result['temperature'],
            'lead_score_breakdown' => $result['breakdown'],
            'source_campaign' => $sourceCampaign ?: $lead->source_campaign,
            'source_whatsapp_conversation_id' => $sourceConversationId ?: $lead->source_whatsapp_conversation_id,
        ])->save();

        return $lead->fresh();
    }

    /**
     * @return array<string, int>
     */
    protected function keywordScores(): array
    {
        return [
            'saya tertarik' => 20,
            'hubungi saya' => 20,
            'berapa harga' => 15,
            'minta penawaran' => 30,
            'proposal' => 30,
        ];
    }

    protected function temperatureForScore(int $score): string
    {
        if ($score >= 51) {
            return 'hot';
        }

        if ($score >= 21) {
            return 'warm';
        }

        return 'cold';
    }

    protected function containsKeyword(string $message, string $keyword): bool
    {
        return str_contains(mb_strtolower($message), $keyword);
    }

    protected function replyCountForLead(Lead $lead): int
    {
        $phones = collect([$lead->whatsapp, $lead->phone])
            ->filter()
            ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone) ?: $phone)
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return 0;
        }

        $broadcastReplies = WhatsAppBroadcastReply::query()
            ->where(function ($query) use ($phones) {
                foreach ($phones as $phone) {
                    $query->orWhere('phone_number', $phone);
                }
            })
            ->count();

        $inboundMessages = WhatsAppMessage::query()
            ->whereRaw('LOWER(TRIM(direction)) = ?', ['inbound'])
            ->where(function ($query) use ($lead, $phones) {
                $query->where('lead_id', $lead->id);

                foreach ($phones as $phone) {
                    $query->orWhere('phone', $phone)
                        ->orWhereHas('conversation', fn ($conversationQuery) => $conversationQuery->where('phone_number', $phone));
                }
            })
            ->count();

        return $broadcastReplies + $inboundMessages;
    }
}
