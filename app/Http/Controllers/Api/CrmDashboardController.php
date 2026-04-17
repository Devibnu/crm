<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $ticketQuery = Tiket::query();
        $currentUserId = $user?->id;
        $canReadCustomers = $user?->canAccess('read', 'CrmCustomers') ?? false;
        $canReadTickets = $user?->canAccess('read', 'CrmTickets') ?? false;
        $canReadInbox = $user?->canAccess('read', 'CrmInbox') ?? false;
        $canManageUsers = $user?->canAccess('manage', 'Admin') ?? false;

        return response()->json([
            'stats' => [
                'pelanggan' => $canReadCustomers ? Pelanggan::query()->count() : 0,
                'tiket' => $canReadTickets ? (clone $ticketQuery)->count() : 0,
                'tiketAktif' => $canReadTickets ? (clone $ticketQuery)->whereIn('status', ['baru', 'diproses'])->count() : 0,
                'tiketSaya' => $canReadTickets && $currentUserId ? (clone $ticketQuery)->where('assigned_user_id', $currentUserId)->count() : 0,
                'tiketBelumAssigned' => $canReadTickets ? (clone $ticketQuery)->whereNull('assigned_user_id')->count() : 0,
                'percakapan' => $canReadInbox ? Pesan::query()->count() : 0,
                'pengguna' => $canManageUsers ? User::query()->count() : 0,
            ],
            'recentTickets' => $canReadTickets
                ? Tiket::query()
                    ->with([
                        'pelanggan:id,nama',
                        'pesanTerbaru' => fn ($query) => $query->select([
                            'pesan.id',
                            'pesan.tiket_id',
                            'pesan.isi_pesan',
                            'pesan.pengirim',
                            'pesan.created_at',
                        ]),
                    ])
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Tiket $tiket) => [
                        'id' => $tiket->id,
                        'kode' => sprintf('TIK-%03d', $tiket->id),
                        'pelanggan' => $tiket->pelanggan?->nama,
                        'status' => $tiket->status,
                        'prioritas' => $tiket->prioritas,
                        'pesanTerbaru' => $tiket->pesanTerbaru?->isi_pesan,
                        'pengirimTerakhir' => $tiket->pesanTerbaru?->pengirim,
                        'updatedAt' => optional($tiket->updated_at)->toIso8601String(),
                    ])
                    ->values()
                : [],
        ]);
    }
}