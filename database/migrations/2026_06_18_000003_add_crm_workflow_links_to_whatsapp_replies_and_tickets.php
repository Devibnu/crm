<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->after('customer_id')->constrained('leads')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'whatsapp_message_id')) {
                $table->foreignId('whatsapp_message_id')->nullable()->after('lead_id')->constrained('whatsapp_messages')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'whatsapp_broadcast_reply_id')) {
                $table->foreignId('whatsapp_broadcast_reply_id')->nullable()->after('whatsapp_message_id')->constrained('whatsapp_broadcast_replies')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'source_type')) {
                $table->string('source_type')->nullable()->after('whatsapp_broadcast_reply_id');
            }
            if (! Schema::hasColumn('tickets', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }

            $table->index(['source_type', 'source_id'], 'tickets_source_type_source_id_idx');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'ticket_id')) {
                $table->foreignId('ticket_id')->nullable()->after('lead_id')->constrained('tickets')->nullOnDelete();
            }
        });

        Schema::table('whatsapp_broadcast_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcast_replies', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->after('whatsapp_broadcast_recipient_id')->constrained('leads')->nullOnDelete();
            }
            if (! Schema::hasColumn('whatsapp_broadcast_replies', 'ticket_id')) {
                $table->foreignId('ticket_id')->nullable()->after('lead_id')->constrained('tickets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_replies', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_broadcast_replies', 'ticket_id')) {
                $table->dropConstrainedForeignId('ticket_id');
            }
            if (Schema::hasColumn('whatsapp_broadcast_replies', 'lead_id')) {
                $table->dropConstrainedForeignId('lead_id');
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'ticket_id')) {
                $table->dropConstrainedForeignId('ticket_id');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_source_type_source_id_idx');

            if (Schema::hasColumn('tickets', 'source_id')) {
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('tickets', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('tickets', 'whatsapp_broadcast_reply_id')) {
                $table->dropConstrainedForeignId('whatsapp_broadcast_reply_id');
            }
            if (Schema::hasColumn('tickets', 'whatsapp_message_id')) {
                $table->dropConstrainedForeignId('whatsapp_message_id');
            }
            if (Schema::hasColumn('tickets', 'lead_id')) {
                $table->dropConstrainedForeignId('lead_id');
            }
        });
    }
};
