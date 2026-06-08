<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcasts', 'send_mode')) {
                $table->string('send_mode')->default('custom_text')->after('message_template');
            }

            if (! Schema::hasColumn('whatsapp_broadcasts', 'whatsapp_message_template_id')) {
                $table->foreignId('whatsapp_message_template_id')->nullable()->after('send_mode')->constrained('whatsapp_message_templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('whatsapp_broadcasts', 'template_variable_defaults')) {
                $table->json('template_variable_defaults')->nullable()->after('whatsapp_message_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_broadcasts', 'whatsapp_message_template_id')) {
                $table->dropConstrainedForeignId('whatsapp_message_template_id');
            }

            foreach (['template_variable_defaults', 'send_mode'] as $column) {
                if (Schema::hasColumn('whatsapp_broadcasts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
