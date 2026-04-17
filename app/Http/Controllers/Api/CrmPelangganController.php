<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerIdentity;
use App\Models\CustomerTimelineEvent;
use App\Models\Pelanggan;
use App\Support\AppsEmailMailbox;
use App\Support\WhatsAppInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CrmPelangganController extends Controller
{
    public function __construct(
        private readonly AppsEmailMailbox $mailbox,
        private readonly WhatsAppInboxService $whatsAppInbox,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmCustomers');

        $query = Pelanggan::query()
            ->withCount('tiket')
            ->withCount([
                'tiket as tiket_aktif_count' => fn ($builder) => $builder->where('status', '!=', 'selesai'),
            ])
            ->withMax('tiket as last_ticket_activity_at', 'updated_at');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('no_hp', 'ilike', "%{$search}%")
                    ->orWhereHas('identities', function ($identityBuilder) use ($search): void {
                        $identityBuilder->where('value', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($hasTicket = $request->string('has_ticket')->toString()) {
            if ($hasTicket === 'yes') {
                $query->has('tiket');
            } elseif ($hasTicket === 'no') {
                $query->doesntHave('tiket');
            }
        }

        $customers = $query
            ->latest()
            ->get();

        return response()->json([
            'pelanggan' => $customers
                ->map(fn (Pelanggan $pelanggan) => $this->transformCustomerSummary($pelanggan))
                ->values(),
            'data' => $customers
                ->map(fn (Pelanggan $pelanggan) => $this->transformCustomerSummary($pelanggan))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'CrmCustomers');

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:pelanggan,email'],
            'noHp' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'source' => ['nullable', 'string', 'in:manual,inbox,import,form,campaign'],
            'notes' => ['nullable', 'string'],
        ]);

        $pelanggan = DB::transaction(function () use ($request, $validated): Pelanggan {
            $pelanggan = Pelanggan::query()->create([
                'nama' => $validated['nama'],
                'email' => $validated['email'] ?? null,
                'no_hp' => $validated['noHp'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'source' => $validated['source'] ?? 'manual',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncPrimaryIdentity($pelanggan, 'email', $validated['email'] ?? null);
            $this->syncPrimaryIdentity($pelanggan, 'whatsapp', $validated['noHp'] ?? null);

            CustomerTimelineEvent::record(
                $pelanggan,
                'customer_created',
                $request->user(),
                'Customer dibuat',
                'Profil customer baru berhasil dibuat.',
                [
                    'source' => $pelanggan->source,
                    'email' => $pelanggan->email,
                    'noHp' => $pelanggan->no_hp,
                ],
            );

            return $pelanggan->fresh()->loadCount('tiket');
        });

        return response()->json([
            'data' => $this->transformCustomerSummary($pelanggan),
        ], 201);
    }

    public function show(Pelanggan $pelanggan): JsonResponse
    {
        $this->authorizeAbility(request(), 'read', 'CrmCustomers');

        $pelanggan->load([
            'identities' => fn ($builder) => $builder
                ->orderByDesc('is_primary')
                ->orderBy('type')
                ->orderBy('id'),
            'timelineEvents' => fn ($builder) => $builder
                ->with('actor:id,full_name,email')
                ->latest('event_at')
                ->latest('id')
                ->limit(20),
            'tiket' => fn ($builder) => $builder
                ->with('pesanTerbaru')
                ->latest()
                ->limit(5),
        ])->loadCount([
            'tiket',
            'tiket as tiket_aktif_count' => fn ($builder) => $builder->where('status', '!=', 'selesai'),
        ]);

        return response()->json([
            'data' => $this->transformCustomerDetail($pelanggan),
        ]);
    }

    public function update(Request $request, Pelanggan $pelanggan): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmCustomers');

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('pelanggan', 'email')->ignore($pelanggan->id)],
            'noHp' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'source' => ['nullable', 'string', 'in:manual,inbox,import,form,campaign'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $pelanggan): void {
            $previousSnapshot = [
                'nama' => $pelanggan->nama,
                'email' => $pelanggan->email,
                'noHp' => $pelanggan->no_hp,
                'status' => $pelanggan->status,
                'source' => $pelanggan->source,
                'notes' => $pelanggan->notes,
            ];

            $pelanggan->update([
                'nama' => $validated['nama'],
                'email' => $validated['email'] ?? null,
                'no_hp' => $validated['noHp'] ?? null,
                'status' => $validated['status'] ?? $pelanggan->status ?? 'active',
                'source' => $validated['source'] ?? $pelanggan->source,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncPrimaryIdentity($pelanggan, 'email', $validated['email'] ?? null);
            $this->syncPrimaryIdentity($pelanggan, 'whatsapp', $validated['noHp'] ?? null);

            CustomerTimelineEvent::record(
                $pelanggan,
                'customer_updated',
                $request->user(),
                'Customer diperbarui',
                'Profil customer berhasil diperbarui.',
                [
                    'before' => $previousSnapshot,
                    'after' => [
                        'nama' => $pelanggan->nama,
                        'email' => $pelanggan->email,
                        'noHp' => $pelanggan->no_hp,
                        'status' => $pelanggan->status,
                        'source' => $pelanggan->source,
                        'notes' => $pelanggan->notes,
                    ],
                ],
            );
        });

        return response()->json([
            'message' => 'Customer berhasil diperbarui.',
            'data' => $this->transformCustomerDetail($pelanggan->fresh()->load([
                'identities',
                'timelineEvents' => fn ($builder) => $builder->with('actor:id,full_name,email')->latest('event_at')->limit(20),
                'tiket' => fn ($builder) => $builder->with('pesanTerbaru')->latest()->limit(5),
            ])->loadCount([
                'tiket',
                'tiket as tiket_aktif_count' => fn ($builder) => $builder->where('status', '!=', 'selesai'),
            ])),
        ]);
    }

    public function storeIdentity(Request $request, Pelanggan $pelanggan): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmCustomers');

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:email,phone,whatsapp'],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:60'],
            'isPrimary' => ['nullable', 'boolean'],
            'isVerified' => ['nullable', 'boolean'],
        ]);

        $normalizedValue = CustomerIdentity::normalizeValue($validated['type'], $validated['value']);

        if ($normalizedValue === '') {
            return response()->json([
                'message' => 'Nilai identity tidak valid.',
                'errors' => [
                    'value' => ['Nilai identity tidak valid.'],
                ],
            ], 422);
        }

        $this->ensureIdentityIsUnique($validated['type'], $normalizedValue);

        $identity = DB::transaction(function () use ($request, $validated, $normalizedValue, $pelanggan): CustomerIdentity {
            if (($validated['isPrimary'] ?? false) === true) {
                $pelanggan->identities()
                    ->where('type', $validated['type'])
                    ->update(['is_primary' => false]);
            }

            $identity = $pelanggan->identities()->create([
                'type' => $validated['type'],
                'value' => $normalizedValue,
                'label' => $validated['label'] ?? null,
                'is_primary' => $validated['isPrimary'] ?? false,
                'is_verified' => $validated['isVerified'] ?? false,
            ]);

            if ($identity->is_primary) {
                $this->syncPrimaryFieldsFromIdentities($pelanggan->fresh());
            }

            CustomerTimelineEvent::record(
                $pelanggan,
                'identity_added',
                $request->user(),
                'Identity ditambahkan',
                sprintf('Identity %s baru ditambahkan ke customer.', $validated['type']),
                [
                    'identityId' => $identity->id,
                    'type' => $identity->type,
                    'value' => $identity->value,
                    'isPrimary' => $identity->is_primary,
                    'isVerified' => $identity->is_verified,
                ],
            );

            return $identity;
        });

        return response()->json([
            'message' => 'Identity customer berhasil ditambahkan.',
            'data' => $this->transformIdentity($identity->fresh()),
        ], 201);
    }

    public function updateIdentity(Request $request, CustomerIdentity $customerIdentity): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmCustomers');

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:email,phone,whatsapp'],
            'value' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:60'],
            'isPrimary' => ['nullable', 'boolean'],
            'isVerified' => ['nullable', 'boolean'],
        ]);

        $type = $validated['type'] ?? $customerIdentity->type;
        $value = array_key_exists('value', $validated)
            ? CustomerIdentity::normalizeValue($type, $validated['value'])
            : $customerIdentity->value;

        if ($value === '') {
            return response()->json([
                'message' => 'Nilai identity tidak valid.',
                'errors' => [
                    'value' => ['Nilai identity tidak valid.'],
                ],
            ], 422);
        }

        $this->ensureIdentityIsUnique($type, $value, $customerIdentity->id);

        $customer = $customerIdentity->customer;

        DB::transaction(function () use ($request, $validated, $type, $value, $customerIdentity, $customer): void {
            if (($validated['isPrimary'] ?? false) === true) {
                $customer->identities()
                    ->where('type', $type)
                    ->whereKeyNot($customerIdentity->id)
                    ->update(['is_primary' => false]);
            }

            $customerIdentity->update([
                'type' => $type,
                'value' => $value,
                'label' => $validated['label'] ?? $customerIdentity->label,
                'is_primary' => $validated['isPrimary'] ?? $customerIdentity->is_primary,
                'is_verified' => $validated['isVerified'] ?? $customerIdentity->is_verified,
            ]);

            $this->syncPrimaryFieldsFromIdentities($customer->fresh());

            CustomerTimelineEvent::record(
                $customer,
                'identity_updated',
                $request->user(),
                'Identity diperbarui',
                sprintf('Identity %s customer diperbarui.', $customerIdentity->type),
                [
                    'identityId' => $customerIdentity->id,
                    'type' => $customerIdentity->type,
                    'value' => $customerIdentity->value,
                    'isPrimary' => $customerIdentity->is_primary,
                    'isVerified' => $customerIdentity->is_verified,
                ],
            );
        });

        return response()->json([
            'message' => 'Identity customer berhasil diperbarui.',
            'data' => $this->transformIdentity($customerIdentity->fresh()),
        ]);
    }

    public function timeline(Pelanggan $pelanggan): JsonResponse
    {
        $this->authorizeAbility(request(), 'read', 'CrmCustomers');

        return response()->json([
            'data' => $this->buildCustomerTimeline($pelanggan, 20)->values(),
        ]);
    }

    private function ensureIdentityIsUnique(string $type, string $value, ?int $ignoreIdentityId = null): void
    {
        $query = CustomerIdentity::query()
            ->where('type', $type)
            ->where('value', $value);

        if ($ignoreIdentityId) {
            $query->whereKeyNot($ignoreIdentityId);
        }

        if ($query->exists()) {
            abort(response()->json([
                'message' => 'Identity sudah digunakan oleh customer lain.',
                'errors' => [
                    'value' => ['Identity sudah digunakan oleh customer lain.'],
                ],
            ], 422));
        }
    }

    private function syncPrimaryIdentity(Pelanggan $pelanggan, string $type, ?string $value): void
    {
        $existingIdentity = $pelanggan->identities()->where('type', $type)->where('is_primary', true)->first();

        if (! $value) {
            if ($existingIdentity) {
                $existingIdentity->delete();
            }

            return;
        }

        $normalizedValue = CustomerIdentity::normalizeValue($type, $value);

        if ($normalizedValue === '') {
            return;
        }

        $pelanggan->identities()->where('type', $type)->update(['is_primary' => false]);

        $pelanggan->identities()->updateOrCreate(
            [
                'type' => $type,
                'value' => $normalizedValue,
            ],
            [
                'label' => 'primary',
                'is_primary' => true,
            ],
        );
    }

    private function syncPrimaryFieldsFromIdentities(Pelanggan $pelanggan): void
    {
        $primaryEmail = $pelanggan->identities()->where('type', 'email')->where('is_primary', true)->value('value');
        $primaryWhatsapp = $pelanggan->identities()->where('type', 'whatsapp')->where('is_primary', true)->value('value');
        $primaryPhone = $pelanggan->identities()->where('type', 'phone')->where('is_primary', true)->value('value');

        $pelanggan->update([
            'email' => $primaryEmail,
            'no_hp' => $primaryWhatsapp ?: $primaryPhone,
        ]);
    }

    private function transformCustomerSummary(Pelanggan $pelanggan): array
    {
        return [
            'id' => $pelanggan->id,
            'nama' => $pelanggan->nama,
            'name' => $pelanggan->nama,
            'email' => $pelanggan->email,
            'primaryEmail' => $pelanggan->email,
            'noHp' => $pelanggan->no_hp,
            'primaryWhatsapp' => $pelanggan->no_hp,
            'status' => $pelanggan->status ?? 'active',
            'source' => $pelanggan->source,
            'notes' => $pelanggan->notes,
            'jumlahTiket' => $pelanggan->tiket_count ?? 0,
            'ticketCount' => $pelanggan->tiket_count ?? 0,
            'activeTicketCount' => $pelanggan->tiket_aktif_count ?? 0,
            'lastActivityAt' => optional($pelanggan->last_ticket_activity_at)->toIso8601String(),
            'createdAt' => optional($pelanggan->created_at)->toIso8601String(),
        ];
    }

    private function transformCustomerDetail(Pelanggan $pelanggan): array
    {
        $activeTicketCount = $pelanggan->tiket_aktif_count ?? $pelanggan->tiket->where('status', '!=', 'selesai')->count();
        $lastActivityAt = $pelanggan->tiket->max('updated_at');

        return [
            ...$this->transformCustomerSummary($pelanggan),
            'identities' => $pelanggan->identities
                ->map(fn (CustomerIdentity $identity) => $this->transformIdentity($identity))
                ->values(),
            'ticketSummary' => [
                'total' => $pelanggan->tiket_count ?? $pelanggan->tiket->count(),
                'active' => $activeTicketCount,
                'lastActivityAt' => optional($lastActivityAt)->toIso8601String(),
            ],
            'recentTickets' => $pelanggan->tiket
                ->map(fn ($ticket) => [
                    'id' => $ticket->id,
                    'kode' => sprintf('TIK-%03d', $ticket->id),
                    'kategori' => $ticket->kategori,
                    'subjek' => $ticket->subjek,
                    'status' => $ticket->status,
                    'prioritas' => $ticket->prioritas,
                    'batasSla' => optional($ticket->batas_sla)->toIso8601String(),
                    'lastMessage' => $ticket->pesanTerbaru?->isi_pesan,
                    'lastMessageAt' => optional($ticket->pesanTerbaru?->created_at)->toIso8601String(),
                ])
                ->values(),
            'timeline' => $this->buildCustomerTimeline($pelanggan, 20)->values(),
        ];
    }

    private function buildCustomerTimeline(Pelanggan $pelanggan, int $limit): Collection
    {
        $databaseEvents = ($pelanggan->relationLoaded('timelineEvents')
            ? $pelanggan->timelineEvents
            : $pelanggan->timelineEvents()
                ->with('actor:id,full_name,email')
                ->latest('event_at')
                ->latest('id')
                ->limit($limit)
                ->get())
            ->map(fn (CustomerTimelineEvent $event) => $this->transformTimelineEvent($event));

        $emailEvents = collect($this->mailbox->timelineEntriesForCustomer($pelanggan));
        $whatsAppEvents = collect($this->whatsAppInbox->timelineEntriesForCustomer($pelanggan));

        return $databaseEvents
            ->concat($emailEvents)
            ->concat($whatsAppEvents)
            ->sortByDesc(fn (array $event) => strtotime((string) ($event['eventAt'] ?? '1970-01-01T00:00:00Z')))
            ->values()
            ->take($limit);
    }

    private function transformIdentity(CustomerIdentity $identity): array
    {
        return [
            'id' => $identity->id,
            'type' => $identity->type,
            'value' => $identity->value,
            'label' => $identity->label,
            'isPrimary' => $identity->is_primary,
            'isVerified' => $identity->is_verified,
            'createdAt' => optional($identity->created_at)->toIso8601String(),
            'updatedAt' => optional($identity->updated_at)->toIso8601String(),
        ];
    }

    private function transformTimelineEvent(CustomerTimelineEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->event_type,
            'title' => $event->title,
            'description' => $event->description,
            'eventAt' => optional($event->event_at)->toIso8601String(),
            'meta' => $event->meta,
            'user' => $event->actor ? [
                'id' => $event->actor->id,
                'fullName' => $event->actor->full_name,
                'email' => $event->actor->email,
            ] : null,
        ];
    }
}