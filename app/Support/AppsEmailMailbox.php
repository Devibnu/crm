<?php

namespace App\Support;

use App\Models\Pelanggan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AppsEmailMailbox
{
    private const CACHE_KEY = 'apps_email_overrides_v1';
    private const CACHE_KEY_CUSTOM_EMAILS = 'apps_email_custom_messages_v1';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildMailbox(): array
    {
        $emails = array_merge(
            $this->staticEmails(),
            $this->customerEmails(),
            $this->customEmails(),
        );

        $overrides = $this->getOverrides();

        $emails = array_map(fn (array $email): array => $this->applyOverrides($email, $overrides), $emails);

        usort($emails, fn (array $left, array $right): int => strcmp((string) $right['time'], (string) $left['time']));

        return $emails;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getOverrides(): array
    {
        $overrides = Cache::get(self::CACHE_KEY, []);

        return is_array($overrides) ? $overrides : [];
    }

    /**
     * @param  array<int|string, mixed>  $overrides
     */
    public function persistOverrides(array $overrides): void
    {
        Cache::forever(self::CACHE_KEY, $overrides);
    }

    /**
     * @param  array<string, mixed>  $email
     * @return array<string, mixed>
     */
    public function storeCustomEmail(array $email): array
    {
        $customEmails = $this->customEmails();
        $nextId = max(array_merge([1000000], array_map(fn (array $item): int => (int) ($item['id'] ?? 0), $customEmails))) + 1;
        $storedEmail = [
            ...$email,
            'id' => $nextId,
        ];

        $customEmails[] = $storedEmail;

        Cache::forever(self::CACHE_KEY_CUSTOM_EMAILS, array_values($customEmails));

        return $storedEmail;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timelineEntriesForCustomer(Pelanggan $customer): array
    {
        $emailCandidates = array_values(array_filter(array_unique(array_map(
            fn (?string $value): string => Str::lower(trim((string) $value)),
            array_merge(
                [$customer->email],
                $customer->relationLoaded('identities')
                    ? $customer->identities->where('type', 'email')->pluck('value')->all()
                    : $customer->identities()->where('type', 'email')->pluck('value')->all(),
            ),
        ))));

        $normalizedName = Str::lower(trim($customer->nama));
        $flattenedEmails = $this->flattenEmails($this->buildMailbox());

        return collect($flattenedEmails)
            ->filter(function (array $email) use ($emailCandidates, $normalizedName, $customer): bool {
                if (($email['customerId'] ?? null) === $customer->id) {
                    return true;
                }

                $fromEmail = Str::lower(trim((string) ($email['from']['email'] ?? '')));
                $fromName = Str::lower(trim((string) ($email['from']['name'] ?? '')));
                $recipientEmails = collect($email['to'] ?? [])->pluck('email')->map(fn ($value) => Str::lower(trim((string) $value)))->all();

                return in_array($fromEmail, $emailCandidates, true)
                    || $fromName === $normalizedName
                    || count(array_intersect($recipientEmails, $emailCandidates)) > 0;
            })
            ->map(function (array $email): array {
                $isOutgoing = ($email['customerDirection'] ?? null) === 'outgoing'
                    || ($email['folder'] ?? null) === 'sent'
                    || Str::lower((string) ($email['from']['email'] ?? '')) === 'support@kitcrm.id';

                $contactName = $isOutgoing
                    ? (string) (collect($email['to'] ?? [])->first()['name'] ?? 'Customer')
                    : (string) ($email['from']['name'] ?? 'Customer');
                $contactEmail = $isOutgoing
                    ? (string) (collect($email['to'] ?? [])->first()['email'] ?? '')
                    : (string) ($email['from']['email'] ?? '');

                return [
                    'id' => sprintf('email-%s', $email['id']),
                    'type' => $isOutgoing ? 'email_sent' : 'email_received',
                    'title' => $isOutgoing
                        ? sprintf('Email terkirim ke %s', $contactName)
                        : sprintf('Email masuk dari %s', $contactName),
                    'description' => $email['subject'] ?? $contactEmail,
                    'eventAt' => $email['time'] ?? null,
                    'meta' => [
                        'source' => 'email',
                        'emailId' => $email['id'],
                        'direction' => $isOutgoing ? 'outgoing' : 'incoming',
                        'emailAddress' => $contactEmail,
                        'contactName' => $contactName,
                        'subject' => $email['subject'] ?? null,
                    ],
                    'user' => null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticEmails(): array
    {
        return [
            [
                'id' => 50,
                'to' => [['email' => 'support@kitcrm.id', 'name' => 'me']],
                'from' => [
                    'email' => 'campaigns@kitcrm.id',
                    'name' => 'Marketing Ops',
                    'avatar' => $this->avatarUrl('Marketing Ops'),
                ],
                'subject' => 'Performance recap for today\'s active campaigns',
                'cc' => [],
                'bcc' => [],
                'message' => '<p>Ringkasan campaign hari ini sudah siap ditinjau. Fokus utama ada pada follow-up customer dari WhatsApp broadcast dan email nurture.</p>',
                'attachments' => [],
                'isStarred' => true,
                'labels' => ['company', 'important'],
                'time' => now()->subHours(6)->toIso8601String(),
                'replies' => [],
                'folder' => 'inbox',
                'isRead' => true,
                'isDeleted' => false,
                'customerId' => null,
            ],
            [
                'id' => 49,
                'to' => [['email' => 'support@kitcrm.id', 'name' => 'me']],
                'from' => [
                    'email' => 'noreply@partner-example.com',
                    'name' => 'Partner Success',
                    'avatar' => $this->avatarUrl('Partner Success'),
                ],
                'subject' => 'Shared account review for support escalation flow',
                'cc' => [],
                'bcc' => [],
                'message' => '<p>Kami sudah meninjau alur eskalasi support. Ada beberapa rekomendasi untuk routing ticket prioritas tinggi minggu ini.</p>',
                'attachments' => [],
                'isStarred' => false,
                'labels' => ['company'],
                'time' => now()->subDay()->toIso8601String(),
                'replies' => [],
                'folder' => 'inbox',
                'isRead' => false,
                'isDeleted' => false,
                'customerId' => null,
            ],
            [
                'id' => 48,
                'to' => [['email' => 'support@kitcrm.id', 'name' => 'me']],
                'from' => [
                    'email' => 'drafts@kitcrm.id',
                    'name' => 'Draft Queue',
                    'avatar' => $this->avatarUrl('Draft Queue'),
                ],
                'subject' => 'Draft follow-up template for inactive customers',
                'cc' => [],
                'bcc' => [],
                'message' => '<p>Template follow-up untuk customer inactive masih dalam status draft dan siap direview sebelum dipakai ulang.</p>',
                'attachments' => [],
                'isStarred' => false,
                'labels' => ['private'],
                'time' => now()->subDays(2)->toIso8601String(),
                'replies' => [],
                'folder' => 'draft',
                'isRead' => true,
                'isDeleted' => false,
                'customerId' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customerEmails(): array
    {
        return Pelanggan::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->with([
                'tiket' => fn ($builder) => $builder->with('pesanTerbaru')->latest(),
            ])
            ->withCount([
                'tiket as active_ticket_count' => fn ($builder) => $builder->where('status', '!=', 'selesai'),
            ])
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->map(function (Pelanggan $customer): array {
                $latestTicket = $customer->tiket->first();
                $latestMessage = $latestTicket?->pesanTerbaru?->isi_pesan;
                $emailId = 900000 + ($customer->id * 10);
                $time = $latestTicket?->updated_at ?? $customer->updated_at ?? $customer->created_at ?? now();
                $ticketCode = $latestTicket ? sprintf('TIK-%03d', $latestTicket->id) : null;

                return [
                    'id' => $emailId,
                    'to' => [['email' => 'support@kitcrm.id', 'name' => 'me']],
                    'from' => [
                        'email' => $customer->email,
                        'name' => $customer->nama,
                        'avatar' => $this->avatarUrl($customer->nama),
                    ],
                    'subject' => $latestTicket?->subjek
                        ? sprintf('Follow up %s: %s', $ticketCode, $latestTicket->subjek)
                        : sprintf('Follow up untuk %s', $customer->nama),
                    'cc' => [],
                    'bcc' => [],
                    'message' => sprintf(
                        '<p>Halo tim, saya %s. %s</p>',
                        e($customer->nama),
                        e(Str::limit($latestMessage ?: ($customer->notes ?: 'Saya ingin tindak lanjut untuk kebutuhan saya saat ini.'), 180))
                    ),
                    'attachments' => [],
                    'isStarred' => (int) ($customer->active_ticket_count ?? 0) > 0,
                    'labels' => $this->resolveCustomerLabels($customer),
                    'time' => $time->toIso8601String(),
                    'replies' => $latestTicket ? [[
                        'id' => $emailId + 1,
                        'to' => [['email' => $customer->email, 'name' => $customer->nama]],
                        'from' => [
                            'email' => 'support@kitcrm.id',
                            'name' => 'KitCRM Support',
                            'avatar' => $this->avatarUrl('KitCRM Support'),
                        ],
                        'subject' => sprintf('Re: %s', $latestTicket->subjek ?: 'Follow up support'),
                        'cc' => [],
                        'bcc' => [],
                        'message' => '<p>Terima kasih. Tim kami sudah menerima konteks customer ini dan sedang menindaklanjuti melalui workspace CRM.</p>',
                        'attachments' => [],
                        'isStarred' => false,
                        'labels' => ['company'],
                        'time' => $time->copy()->subMinutes(5)->toIso8601String(),
                        'replies' => [],
                        'folder' => 'sent',
                        'isRead' => true,
                        'isDeleted' => false,
                        'customerId' => $customer->id,
                        'customerDirection' => 'outgoing',
                    ]] : [],
                    'folder' => 'inbox',
                    'isRead' => (int) ($customer->active_ticket_count ?? 0) === 0,
                    'isDeleted' => false,
                    'customerId' => $customer->id,
                    'customerDirection' => 'incoming',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customEmails(): array
    {
        $customEmails = Cache::get(self::CACHE_KEY_CUSTOM_EMAILS, []);

        return is_array($customEmails) ? array_values(array_filter($customEmails, 'is_array')) : [];
    }

    /**
     * @param  array<int|string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function applyOverrides(array $email, array $overrides): array
    {
        $override = $overrides[$email['id']] ?? null;

        if (is_array($override)) {
            foreach (($override['data'] ?? []) as $key => $value) {
                $email[$key] = $value;
            }

            if (array_key_exists('labels', $override)) {
                $email['labels'] = $override['labels'];
            }
        }

        $email['replies'] = array_map(fn (array $reply): array => $this->applyOverrides($reply, $overrides), $email['replies'] ?? []);

        return $email;
    }

    /**
     * @param  array<int, array<string, mixed>>  $emails
     * @return array<int, array<string, mixed>>
     */
    private function flattenEmails(array $emails): array
    {
        return array_reduce($emails, function (array $carry, array $email): array {
            $carry[] = $email;

            foreach ($this->flattenEmails($email['replies'] ?? []) as $reply) {
                $carry[] = $reply;
            }

            return $carry;
        }, []);
    }

    /**
     * @return array<int, string>
     */
    private function resolveCustomerLabels(Pelanggan $customer): array
    {
        $labels = ['personal'];

        if ($customer->source === 'campaign' || $customer->source === 'import') {
            $labels[] = 'company';
        }

        if (($customer->active_ticket_count ?? 0) > 0) {
            $labels[] = 'important';
        }

        if ($customer->status === 'inactive') {
            $labels[] = 'private';
        }

        return array_values(array_unique($labels));
    }

    private function avatarUrl(string $name): string
    {
        return sprintf('https://ui-avatars.com/api/?name=%s&background=0F766E&color=ffffff', urlencode($name));
    }
}