<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiket', function (Blueprint $table): void {
            $table->string('kategori', 50)->default('general')->after('pelanggan_id');
            $table->string('subjek', 150)->nullable()->after('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('tiket', function (Blueprint $table): void {
            $table->dropColumn(['kategori', 'subjek']);
        });
    }
};