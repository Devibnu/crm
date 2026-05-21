<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('lead_source')->nullable()->after('source');
            $table->text('last_whatsapp_message')->nullable()->after('assigned_to');
            $table->timestamp('last_whatsapp_at')->nullable()->after('last_whatsapp_message');

            $table->index('lead_source');
        });

        Schema::table('omnichannel_messages', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('customer_id')->constrained('leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('omnichannel_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['lead_source']);
            $table->dropColumn([
                'whatsapp',
                'lead_source',
                'last_whatsapp_message',
                'last_whatsapp_at',
            ]);
        });
    }
};
