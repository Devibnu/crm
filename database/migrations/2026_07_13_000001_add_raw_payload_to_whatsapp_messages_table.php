<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('whatsapp_messages', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('whatsapp_messages', 'raw_payload')) {
                $table->dropColumn('raw_payload');
            }
        });
    }
};
