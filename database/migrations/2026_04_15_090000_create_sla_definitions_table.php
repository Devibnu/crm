<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('category', 60)->nullable()->index();
            $table->string('priority', 30)->index();
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->unsignedInteger('warning_before_minutes')->default(60);
            $table->boolean('auto_escalate')->default(false);
            $table->string('escalation_priority', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('sla_definitions')->insert([
            [
                'name' => 'General Support',
                'description' => 'Default SLA for general service requests.',
                'category' => 'general',
                'priority' => 'medium',
                'first_response_minutes' => 60,
                'resolution_minutes' => 480,
                'warning_before_minutes' => 60,
                'auto_escalate' => false,
                'escalation_priority' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Technical Incident',
                'description' => 'SLA baseline for technical issues that need faster response.',
                'category' => 'technical',
                'priority' => 'high',
                'first_response_minutes' => 30,
                'resolution_minutes' => 240,
                'warning_before_minutes' => 45,
                'auto_escalate' => true,
                'escalation_priority' => 'critical',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Billing Review',
                'description' => 'SLA baseline for billing and invoicing clarifications.',
                'category' => 'billing',
                'priority' => 'medium',
                'first_response_minutes' => 90,
                'resolution_minutes' => 720,
                'warning_before_minutes' => 90,
                'auto_escalate' => false,
                'escalation_priority' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Priority Follow Up',
                'description' => 'SLA baseline for strategic customer follow-up and critical handling.',
                'category' => 'priority-follow-up',
                'priority' => 'high',
                'first_response_minutes' => 15,
                'resolution_minutes' => 120,
                'warning_before_minutes' => 30,
                'auto_escalate' => true,
                'escalation_priority' => 'critical',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_definitions');
    }
};