<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('trigger_source', ['form_submit', 'campaign_engagement', 'social_engagement', 'manual', 'crm_activity']);
            $table->integer('score_value')->default(0);
            $table->string('routing_team')->nullable();
            $table->string('routing_user')->nullable();
            $table->json('conditions')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('auto_assign')->default(false);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('trigger_source');
            $table->index('priority');
            $table->index('status');
            $table->index('auto_assign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_scoring_rules');
    }
};
