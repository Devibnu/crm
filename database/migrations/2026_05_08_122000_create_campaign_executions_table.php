<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('audience_segment_id')->nullable()->constrained('audience_segments')->nullOnDelete();
            $table->enum('channel', ['email', 'whatsapp', 'sms', 'social_media', 'ads']);
            $table->enum('status', ['scheduled', 'running', 'completed', 'failed', 'cancelled'])->default('scheduled');
            $table->string('execution_name');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('response_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('channel');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_executions');
    }
};
