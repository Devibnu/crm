<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_conversations', 'channel')) {
                $table->string('channel')->default('whatsapp')->after('phone_number');
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'phone')) {
                $table->string('phone')->nullable()->after('lead_id');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'direction')) {
                $table->string('direction')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'provider')) {
                $table->string('provider')->nullable()->after('provider_message_id');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'broadcast_id')) {
                $table->foreignId('broadcast_id')->nullable()->after('provider')->constrained('whatsapp_broadcasts')->nullOnDelete();
            }
            if (! Schema::hasColumn('whatsapp_messages', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'broadcast_id')) {
                $table->dropConstrainedForeignId('broadcast_id');
            }
            foreach (['received_at', 'provider', 'direction', 'phone'] as $column) {
                if (Schema::hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_conversations', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
