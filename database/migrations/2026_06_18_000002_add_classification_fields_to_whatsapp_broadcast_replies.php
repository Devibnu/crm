<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcast_replies', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcast_replies', 'reply_type')) {
                $table->string('reply_type')->nullable()->after('message');
            }

            if (! Schema::hasColumn('whatsapp_broadcast_replies', 'sentiment')) {
                $table->string('sentiment')->nullable()->after('reply_type');
            }

            if (! Schema::hasColumn('whatsapp_broadcast_replies', 'action_status')) {
                $table->string('action_status')->nullable()->after('sentiment');
            }

            $table->index(['reply_type', 'action_status'], 'wa_reply_classification_idx');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_replies', function (Blueprint $table) {
            $table->dropIndex('wa_reply_classification_idx');
            $table->dropColumn(['reply_type', 'sentiment', 'action_status']);
        });
    }
};
