<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('phone_number')->index();
            $table->string('channel')->default('whatsapp');
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->enum('status', ['baru', 'open', 'pending', 'closed'])->default('baru');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('assigned_to')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique('phone_number', 'whatsapp_conversations_phone_unique');
            $table->index(['status', 'assigned_to'], 'wa_conversation_status_agent_idx');
            $table->index('last_message_at', 'wa_conversation_last_message_idx');
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('direction')->nullable();
            $table->enum('message_type', ['inbound', 'outbound']);
            $table->text('message');
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->string('provider_message_id')->nullable();
            $table->string('provider')->nullable();
            $table->foreignId('broadcast_id')->nullable()->constrained('whatsapp_broadcasts')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_conversation_id', 'created_at'], 'wa_message_conversation_created_idx');
            $table->index(['message_type', 'status'], 'wa_message_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
