<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->nullable()->unique();
            $table->string('full_name', 120);
            $table->string('email', 160)->nullable()->index();
            $table->string('phone', 40)->nullable()->index();
            $table->string('company', 160)->nullable()->index();
            $table->string('source', 60)->default('manual')->index();
            $table->string('status', 40)->default('new')->index();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('qualification_notes')->nullable();
            $table->timestampTz('last_contacted_at')->nullable();
            $table->timestampTz('qualified_at')->nullable();
            $table->timestampTz('disqualified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};