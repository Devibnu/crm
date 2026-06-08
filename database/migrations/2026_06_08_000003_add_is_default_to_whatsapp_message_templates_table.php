<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_message_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('raw');
                $table->index(['provider_id', 'is_default'], 'wa_templates_provider_default_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_message_templates', 'is_default')) {
                $table->dropIndex('wa_templates_provider_default_idx');
                $table->dropColumn('is_default');
            }
        });
    }
};
