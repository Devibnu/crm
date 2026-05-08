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
        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->enum('related_type', ['lead', 'opportunity', 'customer']);
            $table->unsignedBigInteger('related_id');
            $table->enum('type', ['call', 'whatsapp', 'email', 'meeting', 'note', 'follow_up']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('activity_at')->nullable();
            $table->string('assigned_to')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index('type');
            $table->index('activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
    }
};
