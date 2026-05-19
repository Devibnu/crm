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
        Schema::create('customer_satisfactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->tinyInteger('rating')->default(5);
            $table->text('feedback')->nullable();
            $table->enum('survey_channel', ['email', 'whatsapp', 'phone', 'web'])->default('web');
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('positive');
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_satisfactions');
    }
};
