<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_providers', 'display_phone_number')) {
                $table->string('display_phone_number')->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('whatsapp_providers', 'verified_name')) {
                $table->string('verified_name')->nullable()->after('display_phone_number');
            }

            if (! Schema::hasColumn('whatsapp_providers', 'meta_connection_status')) {
                $table->string('meta_connection_status', 30)->nullable()->after('meta_template_language');
            }

            if (! Schema::hasColumn('whatsapp_providers', 'meta_connection_error')) {
                $table->text('meta_connection_error')->nullable()->after('meta_connection_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            foreach (['meta_connection_error', 'meta_connection_status', 'verified_name', 'display_phone_number'] as $column) {
                if (Schema::hasColumn('whatsapp_providers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
