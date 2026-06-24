<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'conversation_id')) {
                $table->foreignId('conversation_id')
                    ->nullable()
                    ->after('source_whatsapp_conversation_id')
                    ->constrained('whatsapp_conversations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'conversation_id')) {
                $table->dropConstrainedForeignId('conversation_id');
            }
        });
    }
};
