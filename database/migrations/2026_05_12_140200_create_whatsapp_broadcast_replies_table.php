<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcast_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_broadcast_id')->nullable()->constrained('whatsapp_broadcasts')->nullOnDelete();
            $table->foreignId('whatsapp_broadcast_recipient_id')->nullable()->constrained('whatsapp_broadcast_recipients')->nullOnDelete();
            $table->string('sender_name');
            $table->string('phone_number');
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'resolved', 'archived'])->default('unread');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_replies');
    }
};
