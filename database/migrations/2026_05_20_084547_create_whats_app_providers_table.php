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
        Schema::create('whatsapp_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['fonnte', 'wablas', 'meta']);
            $table->string('api_url')->nullable();
            $table->text('api_token')->nullable();
            $table->string('device_id')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_providers');
    }
};
