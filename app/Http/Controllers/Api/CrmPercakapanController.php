<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AktivitasTiket;
use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Support\WhatsAppInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmPercakapanController extends Controller
{
    public function __construct(private readonly WhatsAppInboxService $whatsAppInbox)
    {
    }

    public function whatsappInbox(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmWhatsapp');

        return response()->json([
            'threads' => $this->whatsAppInbox->buildThreads(),
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmInbox');

        $tickets = Tiket::query()
            ->with([
                'pelanggan:id,nama,email,no_hp,created_at',
                'assignedUser:id,full_name,email,role',
                'aktivitas' => fn ($builder) => $builder
                    ->with('aktor:id,full_name,email')
                    ->latest(),
                'pesan' => fn ($builder) => $builder->oldest(),
            ])
            ->latest()
            ->get();

        $customers = Pelanggan::query()
            ->withCount([
                'tiket',
                'tiket as jumlah_tiket_aktif' => fn ($builder) => $builder->where('status', '!=', 'selesai'),
            ])
            ->with([
                'tiket' => fn ($builder) => $builder
                    ->select(['id', 'pelanggan_id', 'status', 'updated_at'])
                    ->latest()
                    ->with([
                        'pesan' => fn ($messageBuilder) => $messageBuilder
                            ->select(['id', 'tiket_id', 'created_at'])
                            ->latest(),
                    ]),
            ])
            ->latest()
            ->get();

        return response()->json([
            'conversations' => $tickets
                ->map(fn (Tiket $tiket) => $this->transformConversation($tiket))
                ->values(),
            'customers' => $customers
                ->map(fn (Pelanggan $pelanggan) => $this->transformCustomer($pelanggan))
                ->values(),
            'summary' => [
                'totalConversations' => $tickets->count(),
                'activeConversations' => $tickets->where('status', '!=', 'selesai')->count(),
                'overdueSla' => $tickets->filter(fn (Tiket $tiket) => $tiket->status !== 'selesai' && $tiket->batas_sla?->isPast())->count(),
                'dueSoonSla' => $tickets->filter(fn (Tiket $tiket) => $tiket->status !== 'selesai' && $tiket->batas_sla?->isFuture() && $tiket->batas_sla?->diffInHours(now()) <= 24)->count(),
            ],
        ]);
    }

    public function reply(Request $request, Tiket $tiket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmInbox');

        $validated = $request->validate([
            'isiPesan' => ['required', 'string'],
            'mode' => ['nullable', 'string', 'in:internal,customer'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $mode = $validated['mode'] ?? 'internal';
        $user = $request->user();

        DB::transaction(function () use ($request, $tiket, $validated, $mode, $user): void {
            $previousStatus = $tiket->status;

            if (!empty($validated['status']) && $validated['status'] !== $tiket->status) {
                $tiket->update([
                    'status' => $validated['status'],
                ]);

                AktivitasTiket::record(
                    $tiket,
                    'status_changed',
                    $user,
                    'Status thread diperbarui',
                    sprintf('Status berubah dari %s ke %s.', $previousStatus, $validated['status']),
                    [
                        'from' => $previousStatus,
                        'to' => $validated['status'],
                    ],
                );
            }

            Pesan::query()->create([
                'tiket_id' => $tiket->id,
                'channel' => $mode === 'customer' ? 'balasan-pelanggan' : 'balasan-internal',
                'isi_pesan' => $validated['isiPesan'],
                'pengirim' => $request->user()->full_name ?? $request->user()->email,
            ]);

            AktivitasTiket::record(
                $tiket,
                $mode === 'customer' ? 'customer_reply' : 'internal_note',
                $user,
                $mode === 'customer' ? 'Balasan agent dicatat' : 'Catatan internal ditambahkan',
                $validated['isiPesan'],
                [
                    'mode' => $mode,
                ],
            );
        });

        return response()->json([
            'message' => $mode === 'customer'
                ? 'Balasan ke pelanggan berhasil disimpan.'
                : 'Catatan internal berhasil disimpan.',
        ]);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmInbox');

        $query = Pesan::query()->with(['tiket.pelanggan:id,nama,email']);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('isi_pesan', 'ilike', "%{$search}%")
                    ->orWhere('pengirim', 'ilike', "%{$search}%")
                    ->orWhere('channel', 'ilike', "%{$search}%");
            });
        }

        return response()->json([
            'percakapan' => $query
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (Pesan $pesan) => [
                    'id' => $pesan->id,
                    'channel' => $pesan->channel,
                    'isiPesan' => $pesan->isi_pesan,
                    'pengirim' => $pesan->pengirim,
                    'createdAt' => optional($pesan->created_at)->toIso8601String(),
                    'tiket' => [
                        'id' => $pesan->tiket?->id,
                        'kode' => $pesan->tiket ? sprintf('TIK-%03d', $pesan->tiket->id) : null,
                        'status' => $pesan->tiket?->status,
                    ],
                    'pelanggan' => [
                        'id' => $pesan->tiket?->pelanggan?->id,
                        'nama' => $pesan->tiket?->pelanggan?->nama,
                        'email' => $pesan->tiket?->pelanggan?->email,
                    ],
                ])
                ->values(),
        ]);
    }

    private function transformConversation(Tiket $tiket): array
    {
        return [
            'id' => $tiket->id,
            'kode' => sprintf('TIK-%03d', $tiket->id),
            'kategori' => $tiket->kategori,
            'subjek' => $tiket->subjek,
            'status' => $tiket->status,
            'prioritas' => $tiket->prioritas,
            'batasSla' => optional($tiket->batas_sla)->toIso8601String(),
            'activityAt' => optional($tiket->pesan->last()?->created_at ?? $tiket->updated_at)->toIso8601String(),
            'assignedUser' => $tiket->assignedUser ? [
                'id' => $tiket->assignedUser->id,
                'fullName' => $tiket->assignedUser->full_name,
                'email' => $tiket->assignedUser->email,
                'role' => $tiket->assignedUser->role,
            ] : null,
            'pelanggan' => [
                'id' => $tiket->pelanggan?->id,
                'nama' => $tiket->pelanggan?->nama,
                'email' => $tiket->pelanggan?->email,
                'noHp' => $tiket->pelanggan?->no_hp,
            ],
            'activities' => $tiket->aktivitas
                ->map(fn (AktivitasTiket $aktivitas) => [
                    'id' => $aktivitas->id,
                    'type' => $aktivitas->tipe,
                    'title' => $aktivitas->judul,
                    'description' => $aktivitas->deskripsi,
                    'createdAt' => optional($aktivitas->created_at)->toIso8601String(),
                    'user' => $aktivitas->aktor ? [
                        'id' => $aktivitas->aktor->id,
                        'fullName' => $aktivitas->aktor->full_name,
                        'email' => $aktivitas->aktor->email,
                    ] : null,
                ])
                ->values(),
            'pesan' => $tiket->pesan
                ->map(fn (Pesan $pesan) => [
                    'id' => $pesan->id,
                    'channel' => $pesan->channel,
                    'isiPesan' => $pesan->isi_pesan,
                    'pengirim' => $pesan->pengirim,
                    'createdAt' => optional($pesan->created_at)->toIso8601String(),
                    'senderType' => $pesan->channel === 'balasan-internal'
                        ? 'internal'
                        : ($pesan->channel === 'balasan-pelanggan' ? 'agent' : 'customer'),
                ])
                ->values(),
        ];
    }

    private function transformCustomer(Pelanggan $pelanggan): array
    {
        $latestActivity = $pelanggan->tiket
            ->flatMap(fn (Tiket $tiket) => $tiket->pesan->pluck('created_at'))
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'id' => $pelanggan->id,
            'nama' => $pelanggan->nama,
            'email' => $pelanggan->email,
            'noHp' => $pelanggan->no_hp,
            'jumlahTiket' => $pelanggan->tiket_count,
            'jumlahTiketAktif' => $pelanggan->jumlah_tiket_aktif,
            'createdAt' => optional($pelanggan->created_at)->toIso8601String(),
            'lastActivityAt' => optional($latestActivity ?? $pelanggan->updated_at)->toIso8601String(),
        ];
    }
}