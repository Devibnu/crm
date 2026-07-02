<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'opportunity_id')) {
                $table->foreignId('opportunity_id')
                    ->nullable()
                    ->constrained('opportunities')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('quotations', 'lead_id')) {
                $table->foreignId('lead_id')
                    ->nullable()
                    ->after('opportunity_id')
                    ->constrained('leads')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('quotations', 'conversation_id')) {
                $table->foreignId('conversation_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('whatsapp_conversations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'conversation_id')) {
                $table->dropConstrainedForeignId('conversation_id');
            }

            if (Schema::hasColumn('quotations', 'lead_id')) {
                $table->dropConstrainedForeignId('lead_id');
            }
        });
    }
};
