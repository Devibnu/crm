<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_broadcasts MODIFY status ENUM('draft','scheduled','sending','paused','completed','failed','cancelled') DEFAULT 'draft'");
            DB::statement("ALTER TABLE whatsapp_broadcast_recipients MODIFY status ENUM('queued','sending','sent','delivered','read','replied','failed') DEFAULT 'queued'");
        }

        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->unsignedInteger('total_sent')->default(0)->after('sent_count');
            $table->unsignedInteger('total_failed')->default(0)->after('failed_count');
            $table->decimal('delivery_rate', 5, 2)->default(0)->after('total_failed');
            $table->decimal('reply_rate', 5, 2)->default(0)->after('delivery_rate');
        });

        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->after('replied_at');
            $table->text('error_message')->nullable()->after('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->dropColumn(['provider_message_id', 'error_message']);
        });

        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->dropColumn(['total_sent', 'total_failed', 'delivery_rate', 'reply_rate']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_broadcast_recipients MODIFY status ENUM('queued','sent','delivered','read','replied','failed') DEFAULT 'queued'");
            DB::statement("ALTER TABLE whatsapp_broadcasts MODIFY status ENUM('draft','scheduled','sending','completed','failed','cancelled') DEFAULT 'draft'");
        }
    }
};
