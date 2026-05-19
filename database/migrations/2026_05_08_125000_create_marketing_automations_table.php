<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('audience_segment_id')->nullable()->constrained('audience_segments')->nullOnDelete();
            $table->string('name');
            $table->enum('trigger_type', ['form_submit', 'lead_created', 'campaign_opened', 'link_clicked', 'manual']);
            $table->enum('action_type', ['send_email', 'send_whatsapp', 'assign_sales', 'add_to_segment', 'create_task']);
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->integer('delay_minutes')->default(0);
            $table->json('conditions')->nullable();
            $table->json('action_payload')->nullable();
            $table->integer('executed_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('trigger_type');
            $table->index('action_type');
            $table->index('status');
            $table->index('last_executed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_automations');
    }
};
