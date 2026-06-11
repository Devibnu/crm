<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'media_path')) {
                $table->string('media_path')->nullable()->after('error_message');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_original_name')) {
                $table->string('media_original_name')->nullable()->after('media_path');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_mime')) {
                $table->string('media_mime')->nullable()->after('media_original_name');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_size')) {
                $table->unsignedBigInteger('media_size')->nullable()->after('media_mime');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_id')) {
                $table->string('media_id')->nullable()->after('media_size');
            }
            if (! Schema::hasColumn('whatsapp_messages', 'media_url')) {
                $table->string('media_url')->nullable()->after('media_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            foreach (['media_url', 'media_id', 'media_size', 'media_mime', 'media_original_name', 'media_path'] as $column) {
                if (Schema::hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
