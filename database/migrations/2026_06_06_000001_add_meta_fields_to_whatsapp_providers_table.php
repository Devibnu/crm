<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_providers', 'business_account_id')) {
                $table->string('business_account_id')->nullable()->after('webhook_secret');
            }

            if (! Schema::hasColumn('whatsapp_providers', 'graph_api_version')) {
                $table->string('graph_api_version', 20)->nullable()->after('business_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_providers', 'graph_api_version')) {
                $table->dropColumn('graph_api_version');
            }

            if (Schema::hasColumn('whatsapp_providers', 'business_account_id')) {
                $table->dropColumn('business_account_id');
            }
        });
    }
};
