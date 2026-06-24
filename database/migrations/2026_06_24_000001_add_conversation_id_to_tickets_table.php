<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'conversation_id')) {
                $table->foreignId('conversation_id')
                    ->nullable()
                    ->after('whatsapp_broadcast_reply_id')
                    ->constrained('whatsapp_conversations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'conversation_id')) {
                $table->dropConstrainedForeignId('conversation_id');
            }
        });
    }
};
