<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->after('no_hp')->index();
            $table->string('source', 50)->nullable()->after('status')->index();
            $table->text('notes')->nullable()->after('source');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('pelanggan', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['status', 'source', 'notes']);
        });

        Schema::table('pelanggan', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }
};