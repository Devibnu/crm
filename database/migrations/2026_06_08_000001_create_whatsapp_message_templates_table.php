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
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('whatsapp_providers')->cascadeOnDelete();
            $table->string('template_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('language', 20);
            $table->string('status')->nullable();
            $table->text('body')->nullable();
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'name', 'language'], 'wa_templates_provider_name_language_unique');
            $table->index(['provider_id', 'status']);
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
