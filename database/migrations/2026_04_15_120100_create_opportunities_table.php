<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->nullable()->unique();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('stage', 40)->default('prospecting')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->unsignedTinyInteger('probability')->default(20);
            $table->date('expected_close_date')->nullable()->index();
            $table->text('status_notes')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};