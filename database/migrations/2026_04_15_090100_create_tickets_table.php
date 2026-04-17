<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->nullable()->unique();
            $table->foreignId('customer_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sla_definition_id')->nullable()->constrained('sla_definitions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 160);
            $table->longText('description');
            $table->string('category', 60)->index();
            $table->string('status', 40)->default('open')->index();
            $table->string('priority', 30)->default('medium')->index();
            $table->unsignedSmallInteger('escalation_level')->default(0);
            $table->string('alert_state', 40)->default('on_track')->index();
            $table->timestampTz('first_response_due_at')->nullable()->index();
            $table->timestampTz('resolution_due_at')->nullable()->index();
            $table->timestampTz('first_responded_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('last_activity_at')->nullable()->index();
            $table->timestampTz('alert_sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};