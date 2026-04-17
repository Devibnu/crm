<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tickets = DB::table('tiket')
            ->leftJoin('aktivitas_tiket', 'aktivitas_tiket.tiket_id', '=', 'tiket.id')
            ->whereNull('aktivitas_tiket.id')
            ->select([
                'tiket.id',
                'tiket.kategori',
                'tiket.prioritas',
                'tiket.assigned_user_id',
                'tiket.created_at',
            ])
            ->get();

        foreach ($tickets as $ticket) {
            DB::table('aktivitas_tiket')->insert([
                'tiket_id' => $ticket->id,
                'user_id' => $ticket->assigned_user_id,
                'tipe' => 'ticket_created',
                'judul' => 'Tiket dibuat',
                'deskripsi' => sprintf('Tiket %s dibuat dengan prioritas %s.', $ticket->kategori, $ticket->prioritas),
                'metadata' => json_encode([
                    'kategori' => $ticket->kategori,
                    'prioritas' => $ticket->prioritas,
                ], JSON_THROW_ON_ERROR),
                'created_at' => $ticket->created_at,
            ]);

            $firstMessage = DB::table('pesan')
                ->where('tiket_id', $ticket->id)
                ->orderBy('created_at')
                ->first(['isi_pesan', 'created_at']);

            if ($firstMessage?->isi_pesan) {
                DB::table('aktivitas_tiket')->insert([
                    'tiket_id' => $ticket->id,
                    'user_id' => $ticket->assigned_user_id,
                    'tipe' => 'internal_note',
                    'judul' => 'Catatan awal ditambahkan',
                    'deskripsi' => $firstMessage->isi_pesan,
                    'metadata' => null,
                    'created_at' => $firstMessage->created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('aktivitas_tiket')
            ->whereIn('tipe', ['ticket_created', 'internal_note'])
            ->delete();
    }
};