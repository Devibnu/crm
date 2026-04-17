<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AktivitasTiket;
use App\Models\Pesan;
use App\Models\Pelanggan;
use App\Models\Tiket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmTiketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmTickets');

        $query = Tiket::query()
            ->with([
                'pelanggan:id,nama,email',
                'assignedUser:id,full_name,email,role',
                'aktivitas' => fn ($builder) => $builder
                    ->with('aktor:id,full_name,email')
                    ->latest(),
                'pesan' => fn ($builder) => $builder->latest(),
            ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($kategori = $request->string('kategori')->toString()) {
            $query->where('kategori', $kategori);
        }

        return response()->json([
            'tiket' => $query
                ->latest()
                ->get()
                ->map(fn (Tiket $tiket) => $this->transformTicket($tiket))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'CrmTickets');

        $validated = $request->validate([
            'pelangganId' => ['required', 'integer', 'exists:pelanggan,id'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'kategori' => ['required', 'string', 'in:general,billing,technical,priority-follow-up'],
            'subjek' => ['nullable', 'string', 'max:150'],
            'prioritas' => ['required', 'string', 'in:rendah,sedang,tinggi'],
            'isiPesan' => ['required', 'string'],
        ]);

        $pelanggan = Pelanggan::query()->findOrFail($validated['pelangganId']);

        $tiket = DB::transaction(function () use ($request, $pelanggan, $validated): Tiket {
            $tiket = Tiket::query()->create([
                'pelanggan_id' => $pelanggan->id,
                'assigned_user_id' => $validated['assignedUserId'] ?? null,
                'kategori' => $validated['kategori'],
                'subjek' => $validated['subjek'] ?: null,
                'status' => 'baru',
                'prioritas' => $validated['prioritas'],
                'batas_sla' => $this->resolveSlaDeadline($validated['prioritas']),
            ]);

            Pesan::query()->create([
                'tiket_id' => $tiket->id,
                'channel' => 'balasan-internal',
                'isi_pesan' => $validated['isiPesan'],
                'pengirim' => $request->user()->full_name ?? $request->user()->email,
            ]);

            AktivitasTiket::record(
                $tiket,
                'ticket_created',
                $request->user(),
                'Tiket dibuat',
                sprintf('Tiket %s dibuat dengan prioritas %s.', $validated['kategori'], $validated['prioritas']),
                [
                    'kategori' => $validated['kategori'],
                    'prioritas' => $validated['prioritas'],
                    'assignedUserId' => $validated['assignedUserId'] ?? null,
                ],
            );

            AktivitasTiket::record(
                $tiket,
                'internal_note',
                $request->user(),
                'Catatan awal ditambahkan',
                $validated['isiPesan'],
            );

            return $tiket->load([
                'pelanggan:id,nama,email',
                'assignedUser:id,full_name,email,role',
                'aktivitas' => fn ($builder) => $builder
                    ->with('aktor:id,full_name,email')
                    ->latest(),
                'pesan' => fn ($builder) => $builder->latest(),
            ]);
        });

        return response()->json([
            'message' => 'Tiket baru berhasil dibuat.',
            'tiket' => $this->transformTicket($tiket),
        ], 201);
    }

    public function balas(Request $request, Tiket $tiket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'isiPesan' => ['required', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($request, $tiket, $validated): void {
            $previousStatus = $tiket->status;

            $tiket->update([
                'status' => $validated['status'],
            ]);

            Pesan::query()->create([
                'tiket_id' => $tiket->id,
                'channel' => 'balasan-internal',
                'isi_pesan' => $validated['isiPesan'],
                'pengirim' => $request->user()->full_name ?? $request->user()->email,
            ]);

            if ($previousStatus !== $validated['status']) {
                AktivitasTiket::record(
                    $tiket,
                    'status_changed',
                    $request->user(),
                    'Status tiket diperbarui',
                    sprintf('Status berubah dari %s ke %s.', $previousStatus, $validated['status']),
                    [
                        'from' => $previousStatus,
                        'to' => $validated['status'],
                    ],
                );
            }

            AktivitasTiket::record(
                $tiket,
                'internal_note',
                $request->user(),
                'Balasan tiket dicatat',
                $validated['isiPesan'],
            );
        });

        return response()->json([
            'message' => 'Balasan tiket berhasil disimpan.',
        ]);
    }

    public function updateStatus(Request $request, Tiket $tiket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:baru,diproses,selesai'],
        ]);

        $previousStatus = $tiket->status;

        $tiket->update([
            'status' => $validated['status'],
        ]);

        if ($previousStatus !== $validated['status']) {
            AktivitasTiket::record(
                $tiket,
                'status_changed',
                $request->user(),
                'Status tiket diperbarui',
                sprintf('Status berubah dari %s ke %s.', $previousStatus, $validated['status']),
                [
                    'from' => $previousStatus,
                    'to' => $validated['status'],
                ],
            );
        }

        return response()->json([
            'message' => 'Status tiket berhasil diperbarui.',
            'tiket' => $this->transformTicket($tiket->fresh([
                'pelanggan:id,nama,email',
                'assignedUser:id,full_name,email,role',
                'aktivitas' => fn ($builder) => $builder
                    ->with('aktor:id,full_name,email')
                    ->latest(),
                'pesan' => fn ($builder) => $builder->latest(),
            ])),
        ]);
    }

    public function assign(Request $request, Tiket $tiket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $previousAssignee = $tiket->assignedUser;

        $assignedUser = null;

        if (! empty($validated['assignedUserId'])) {
            $assignedUser = User::query()->findOrFail($validated['assignedUserId']);
        }

        $tiket->update([
            'assigned_user_id' => $assignedUser?->id,
        ]);

        if (($previousAssignee?->id ?? null) !== ($assignedUser?->id ?? null)) {
            AktivitasTiket::record(
                $tiket,
                'assignment_changed',
                $request->user(),
                'Agent penanggung jawab diperbarui',
                sprintf(
                    'Agent berubah dari %s ke %s.',
                    $previousAssignee?->full_name ?? 'Belum di-assign',
                    $assignedUser?->full_name ?? 'Belum di-assign',
                ),
                [
                    'fromUserId' => $previousAssignee?->id,
                    'toUserId' => $assignedUser?->id,
                ],
            );
        }

        return response()->json([
            'message' => 'Agent tiket berhasil diperbarui.',
            'tiket' => $this->transformTicket($tiket->fresh([
                'pelanggan:id,nama,email',
                'assignedUser:id,full_name,email,role',
                'aktivitas' => fn ($builder) => $builder
                    ->with('aktor:id,full_name,email')
                    ->latest(),
                'pesan' => fn ($builder) => $builder->latest(),
            ])),
        ]);
    }

    private function resolveSlaDeadline(string $priority): CarbonImmutable
    {
        $hours = match ($priority) {
            'tinggi' => 4,
            'sedang' => 24,
            default => 72,
        };

        return CarbonImmutable::now()->addHours($hours);
    }

    private function transformTicket(Tiket $tiket): array
    {
        return [
            'id' => $tiket->id,
            'kode' => sprintf('TIK-%03d', $tiket->id),
            'kategori' => $tiket->kategori,
            'subjek' => $tiket->subjek,
            'status' => $tiket->status,
            'prioritas' => $tiket->prioritas,
            'batasSla' => optional($tiket->batas_sla)->toIso8601String(),
            'assignedUser' => $tiket->assignedUser ? [
                'id' => $tiket->assignedUser->id,
                'fullName' => $tiket->assignedUser->full_name,
                'email' => $tiket->assignedUser->email,
                'role' => $tiket->assignedUser->role,
            ] : null,
            'activities' => $tiket->aktivitas->map(fn (AktivitasTiket $aktivitas) => [
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
            ])->values(),
            'pelanggan' => [
                'id' => $tiket->pelanggan?->id,
                'nama' => $tiket->pelanggan?->nama,
                'email' => $tiket->pelanggan?->email,
            ],
            'pesan' => $tiket->pesan->map(fn (Pesan $pesan) => [
                'id' => $pesan->id,
                'channel' => $pesan->channel,
                'isiPesan' => $pesan->isi_pesan,
                'pengirim' => $pesan->pengirim,
                'createdAt' => optional($pesan->created_at)->toIso8601String(),
            ])->values(),
        ];
    }
}