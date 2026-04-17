<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $customers = DB::table('pelanggan')->get();

        foreach ($customers as $customer) {
            DB::table('pelanggan')
                ->where('id', $customer->id)
                ->update([
                    'status' => $customer->status ?: 'active',
                    'source' => $customer->source ?: 'manual',
                ]);

            if ($customer->email) {
                DB::table('customer_identities')->updateOrInsert(
                    [
                        'type' => 'email',
                        'value' => strtolower(trim($customer->email)),
                    ],
                    [
                        'customer_id' => $customer->id,
                        'label' => 'primary',
                        'is_primary' => true,
                        'is_verified' => false,
                        'created_at' => $customer->created_at ?? $now,
                        'updated_at' => $customer->updated_at ?? $now,
                    ],
                );
            }

            if ($customer->no_hp) {
                DB::table('customer_identities')->updateOrInsert(
                    [
                        'type' => 'whatsapp',
                        'value' => preg_replace('/\D+/', '', $customer->no_hp),
                    ],
                    [
                        'customer_id' => $customer->id,
                        'label' => 'primary',
                        'is_primary' => true,
                        'is_verified' => false,
                        'created_at' => $customer->created_at ?? $now,
                        'updated_at' => $customer->updated_at ?? $now,
                    ],
                );
            }

            DB::table('customer_timeline_events')->updateOrInsert(
                [
                    'customer_id' => $customer->id,
                    'event_type' => 'customer_created',
                ],
                [
                    'user_id' => $customer->created_by,
                    'title' => 'Customer dibuat',
                    'description' => 'Profil customer awal dimigrasikan ke fondasi customer master.',
                    'meta' => json_encode(['source' => $customer->source ?: 'manual']),
                    'event_at' => $customer->created_at ?? $now,
                    'created_at' => $customer->created_at ?? $now,
                    'updated_at' => $customer->updated_at ?? $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('customer_timeline_events')->where('event_type', 'customer_created')->delete();
        DB::table('customer_identities')->whereIn('type', ['email', 'whatsapp'])->delete();
    }
};