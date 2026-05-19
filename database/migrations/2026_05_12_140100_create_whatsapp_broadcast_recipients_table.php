<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_broadcast_id')->constrained('whatsapp_broadcasts')->cascadeOnDelete();
            $table->enum('recipient_type', ['customer', 'lead']);
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name');
            $table->string('phone_number');
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'replied', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_broadcast_id', 'status'], 'wa_broadcast_recipient_status_idx');
            $table->index(['recipient_type', 'recipient_id'], 'wa_broadcast_recipient_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_recipients');
    }
};
