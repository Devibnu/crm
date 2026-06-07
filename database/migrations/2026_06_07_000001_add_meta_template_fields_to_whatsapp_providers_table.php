<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_providers', 'meta_template_name')) {
                $table->string('meta_template_name')->nullable()->after('graph_api_version');
            }

            if (! Schema::hasColumn('whatsapp_providers', 'meta_template_language')) {
                $table->string('meta_template_language', 20)->nullable()->after('meta_template_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_providers', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_providers', 'meta_template_language')) {
                $table->dropColumn('meta_template_language');
            }

            if (Schema::hasColumn('whatsapp_providers', 'meta_template_name')) {
                $table->dropColumn('meta_template_name');
            }
        });
    }
};
