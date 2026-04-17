<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerTimelineEvent;
use App\Models\Pelanggan;
use App\Support\AppsEmailMailbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AppsEmailController extends Controller
{
    public function __construct(private readonly AppsEmailMailbox $mailbox)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmInbox');

        $query = Str::lower(trim($request->string('q')->toString()));
        $filter = $request->string('filter')->toString() ?: 'inbox';
        $label = $request->string('label')->toString();

        $emails = $this->mailbox->buildMailbox();

        $filteredEmails = array_values(array_filter($emails, function (array $email) use ($filter, $label, $query): bool {
            return $this->matchesQuery($email, $query)
                && $this->matchesFilter($email, $filter)
                && $this->matchesLabel($email, $label);
        }));

        $emailsMeta = [
            'inbox' => count(array_filter($emails, fn (array $email): bool => ! $email['isDeleted'] && ! $email['isRead'] && $email['folder'] === 'inbox')),
            'draft' => count(array_filter($emails, fn (array $email): bool => ! $email['isDeleted'] && $email['folder'] === 'draft')),
            'spam' => count(array_filter($emails, fn (array $email): bool => ! $email['isDeleted'] && ! $email['isRead'] && $email['folder'] === 'spam')),
            'star' => count(array_filter($emails, fn (array $email): bool => ! $email['isDeleted'] && $email['isStarred'])),
        ];

        return response()->json([
            'emails' => $filteredEmails,
            'emailsMeta' => $emailsMeta,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmInbox');

        $validated = $request->validate([
            'ids' => ['required'],
            'data' => ['nullable', 'array'],
            'label' => ['nullable', 'string', 'in:personal,company,important,private'],
        ]);

        $ids = array_values(array_unique(array_map('intval', Arr::wrap($validated['ids']))));
        $data = collect($validated['data'] ?? [])->only([
            'folder',
            'isDeleted',
            'isRead',
            'isStarred',
        ])->toArray();
        $label = $validated['label'] ?? null;

        $mailboxById = collect($this->mailbox->buildMailbox())->keyBy('id');
        $overrides = $this->mailbox->getOverrides();

        foreach ($ids as $id) {
            if (! $mailboxById->has($id)) {
                continue;
            }

            $currentEmail = $mailboxById->get($id);
            $currentOverride = $overrides[$id] ?? [];

            if ($label !== null) {
                $labels = $currentOverride['labels'] ?? $currentEmail['labels'];
                $labelIndex = array_search($label, $labels, true);
                $wasAdded = $labelIndex === false;

                if ($wasAdded) {
                    $labels[] = $label;
                } else {
                    unset($labels[$labelIndex]);
                }

                $currentOverride['labels'] = array_values($labels);

                $this->recordLabelEvent($request, $currentEmail, $label, $wasAdded);
            } elseif ($data !== []) {
                $this->recordDataEvents($request, $currentEmail, $data);
                $currentOverride['data'] = array_merge($currentOverride['data'] ?? [], $data);
            }

            $overrides[$id] = $currentOverride;
        }

        $this->mailbox->persistOverrides($overrides);

        return response()->json(null, 201);
    }

    public function send(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'CrmInbox');

        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*.email' => ['required', 'email', 'max:255'],
            'to.*.name' => ['nullable', 'string', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*.email' => ['required_with:cc', 'email', 'max:255'],
            'cc.*.name' => ['nullable', 'string', 'max:255'],
            'bcc' => ['nullable', 'array'],
            'bcc.*.email' => ['required_with:bcc', 'email', 'max:255'],
            'bcc.*.name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'customerId' => ['nullable', 'integer', 'exists:pelanggan,id'],
        ]);

        $customer = $this->resolveCustomerForRecipients($validated);
        $primaryRecipient = $validated['to'][0];

        $email = $this->mailbox->storeCustomEmail([
            'to' => array_map(fn (array $recipient): array => [
                'email' => Str::lower(trim($recipient['email'])),
                'name' => $recipient['name'] ?? $this->resolveRecipientName($recipient['email']),
            ], $validated['to']),
            'from' => [
                'email' => 'support@kitcrm.id',
                'name' => 'KitCRM Support',
                'avatar' => 'https://ui-avatars.com/api/?name=KitCRM+Support&background=0F766E&color=ffffff',
            ],
            'subject' => trim((string) ($validated['subject'] ?? '')) ?: sprintf('Follow up untuk %s', $primaryRecipient['name'] ?? $this->resolveRecipientName($primaryRecipient['email'])),
            'cc' => array_map(fn (array $recipient): array => [
                'email' => Str::lower(trim($recipient['email'])),
                'name' => $recipient['name'] ?? $this->resolveRecipientName($recipient['email']),
            ], $validated['cc'] ?? []),
            'bcc' => array_map(fn (array $recipient): array => [
                'email' => Str::lower(trim($recipient['email'])),
                'name' => $recipient['name'] ?? $this->resolveRecipientName($recipient['email']),
            ], $validated['bcc'] ?? []),
            'message' => trim((string) ($validated['message'] ?? '')) ?: '<p>Follow up dari workspace email KitCRM.</p>',
            'attachments' => [],
            'isStarred' => false,
            'labels' => $customer ? ['company', 'important'] : ['company'],
            'time' => now()->toIso8601String(),
            'replies' => [],
            'folder' => 'sent',
            'isRead' => true,
            'isDeleted' => false,
            'customerId' => $customer?->id,
            'customerDirection' => 'outgoing',
        ]);

        return response()->json([
            'data' => $email,
        ], 201);
    }

    private function matchesFilter(array $email, string $filter): bool
    {
        if ($filter === 'trashed') {
            return (bool) $email['isDeleted'];
        }

        if ($filter === 'starred') {
            return (bool) $email['isStarred'] && ! $email['isDeleted'];
        }

        return $email['folder'] === ($filter ?: $email['folder']) && ! $email['isDeleted'];
    }

    private function matchesLabel(array $email, string $label): bool
    {
        if ($label === '') {
            return true;
        }

        return in_array($label, $email['labels'], true);
    }

    private function matchesQuery(array $email, string $query): bool
    {
        if ($query === '') {
            return true;
        }

        $haystacks = [
            Str::lower($email['from']['name'] ?? ''),
            Str::lower($email['from']['email'] ?? ''),
            Str::lower($email['subject'] ?? ''),
        ];

        foreach ($haystacks as $haystack) {
            if (str_contains($haystack, $query)) {
                return true;
            }
        }

        return false;
    }

    private function recordDataEvents(Request $request, array $currentEmail, array $data): void
    {
        $customer = $this->resolveCustomer($currentEmail);

        if (! $customer) {
            return;
        }

        if (array_key_exists('folder', $data) && ($data['folder'] ?? null) !== ($currentEmail['folder'] ?? null)) {
            CustomerTimelineEvent::record(
                $customer,
                'email_moved',
                $request->user(),
                sprintf('Email dipindahkan ke %s', $data['folder']),
                (string) ($currentEmail['subject'] ?? $currentEmail['from']['email'] ?? 'Email customer'),
                [
                    'source' => 'email',
                    'emailId' => $currentEmail['id'] ?? null,
                    'emailAddress' => $currentEmail['from']['email'] ?? null,
                    'fromFolder' => $currentEmail['folder'] ?? null,
                    'toFolder' => $data['folder'],
                ],
            );
        }

        if (array_key_exists('isRead', $data) && (bool) $data['isRead'] !== (bool) ($currentEmail['isRead'] ?? false)) {
            CustomerTimelineEvent::record(
                $customer,
                (bool) $data['isRead'] ? 'email_marked_read' : 'email_marked_unread',
                $request->user(),
                (bool) $data['isRead'] ? 'Email ditandai sudah dibaca' : 'Email ditandai belum dibaca',
                (string) ($currentEmail['subject'] ?? $currentEmail['from']['email'] ?? 'Email customer'),
                [
                    'source' => 'email',
                    'emailId' => $currentEmail['id'] ?? null,
                    'emailAddress' => $currentEmail['from']['email'] ?? null,
                ],
            );
        }

        if (array_key_exists('isStarred', $data) && (bool) $data['isStarred'] !== (bool) ($currentEmail['isStarred'] ?? false)) {
            CustomerTimelineEvent::record(
                $customer,
                (bool) $data['isStarred'] ? 'email_starred' : 'email_unstarred',
                $request->user(),
                (bool) $data['isStarred'] ? 'Email diberi bintang' : 'Bintang email dihapus',
                (string) ($currentEmail['subject'] ?? $currentEmail['from']['email'] ?? 'Email customer'),
                [
                    'source' => 'email',
                    'emailId' => $currentEmail['id'] ?? null,
                    'emailAddress' => $currentEmail['from']['email'] ?? null,
                ],
            );
        }

        if (array_key_exists('isDeleted', $data) && (bool) $data['isDeleted'] !== (bool) ($currentEmail['isDeleted'] ?? false)) {
            CustomerTimelineEvent::record(
                $customer,
                (bool) $data['isDeleted'] ? 'email_trashed' : 'email_restored',
                $request->user(),
                (bool) $data['isDeleted'] ? 'Email dipindahkan ke trash' : 'Email dipulihkan dari trash',
                (string) ($currentEmail['subject'] ?? $currentEmail['from']['email'] ?? 'Email customer'),
                [
                    'source' => 'email',
                    'emailId' => $currentEmail['id'] ?? null,
                    'emailAddress' => $currentEmail['from']['email'] ?? null,
                ],
            );
        }
    }

    private function recordLabelEvent(Request $request, array $currentEmail, string $label, bool $wasAdded): void
    {
        $customer = $this->resolveCustomer($currentEmail);

        if (! $customer) {
            return;
        }

        CustomerTimelineEvent::record(
            $customer,
            $wasAdded ? 'email_label_added' : 'email_label_removed',
            $request->user(),
            $wasAdded ? sprintf('Label email ditambahkan: %s', $label) : sprintf('Label email dihapus: %s', $label),
            (string) ($currentEmail['subject'] ?? $currentEmail['from']['email'] ?? 'Email customer'),
            [
                'source' => 'email',
                'emailId' => $currentEmail['id'] ?? null,
                'emailAddress' => $currentEmail['from']['email'] ?? null,
                'label' => $label,
            ],
        );
    }

    private function resolveCustomer(array $currentEmail): ?Pelanggan
    {
        $customerId = $currentEmail['customerId'] ?? null;

        if (! is_numeric($customerId)) {
            return null;
        }

        return Pelanggan::query()->find((int) $customerId);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCustomerForRecipients(array $validated): ?Pelanggan
    {
        if (isset($validated['customerId']) && is_numeric($validated['customerId'])) {
            return Pelanggan::query()->find((int) $validated['customerId']);
        }

        $emails = collect($validated['to'] ?? [])
            ->pluck('email')
            ->map(fn ($email) => Str::lower(trim((string) $email)))
            ->filter()
            ->values();

        if ($emails->isEmpty()) {
            return null;
        }

        return Pelanggan::query()
            ->whereIn('email', $emails->all())
            ->first();
    }

    private function resolveRecipientName(string $email): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', Str::before($email, '@')));
    }
}