<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'lead_score')) {
                $table->unsignedSmallInteger('lead_score')->default(0)->after('last_whatsapp_at');
            }
            if (! Schema::hasColumn('leads', 'lead_temperature')) {
                $table->string('lead_temperature', 20)->default('cold')->after('lead_score');
            }
            if (! Schema::hasColumn('leads', 'lead_score_breakdown')) {
                $table->json('lead_score_breakdown')->nullable()->after('lead_temperature');
            }
            if (! Schema::hasColumn('leads', 'source_campaign')) {
                $table->string('source_campaign')->nullable()->after('lead_score_breakdown');
            }
            if (! Schema::hasColumn('leads', 'source_whatsapp_conversation_id')) {
                $table->foreignId('source_whatsapp_conversation_id')
                    ->nullable()
                    ->after('source_campaign')
                    ->constrained('whatsapp_conversations')
                    ->nullOnDelete();
            }

            $table->index(['lead_temperature', 'lead_score'], 'leads_temperature_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_temperature_score_idx');

            if (Schema::hasColumn('leads', 'source_whatsapp_conversation_id')) {
                $table->dropConstrainedForeignId('source_whatsapp_conversation_id');
            }
            if (Schema::hasColumn('leads', 'source_campaign')) {
                $table->dropColumn('source_campaign');
            }
            if (Schema::hasColumn('leads', 'lead_score_breakdown')) {
                $table->dropColumn('lead_score_breakdown');
            }
            if (Schema::hasColumn('leads', 'lead_temperature')) {
                $table->dropColumn('lead_temperature');
            }
            if (Schema::hasColumn('leads', 'lead_score')) {
                $table->dropColumn('lead_score');
            }
        });
    }
};
