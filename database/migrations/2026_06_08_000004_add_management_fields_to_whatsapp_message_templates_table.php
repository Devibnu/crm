<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_message_templates', 'safe_name')) {
                $table->string('safe_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'body_meta')) {
                $table->text('body_meta')->nullable()->after('body');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'variable_mapping')) {
                $table->json('variable_mapping')->nullable()->after('buttons');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'source')) {
                $table->string('source')->default('meta_sync')->after('variable_mapping');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('last_synced_at');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('whatsapp_message_templates', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            foreach (['rejected_reason', 'approved_at', 'submitted_at', 'source', 'variable_mapping', 'body_meta', 'safe_name'] as $column) {
                if (Schema::hasColumn('whatsapp_message_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
