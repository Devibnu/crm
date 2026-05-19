<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('omnichannel_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'email', 'livechat', 'facebook', 'instagram', 'telegram']);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('sender_name')->nullable();
            $table->string('sender_contact')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'pending', 'resolved'])->default('unread');
            $table->string('assigned_to')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omnichannel_messages');
    }
};
