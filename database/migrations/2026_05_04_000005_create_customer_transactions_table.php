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
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'won', 'lost', 'cancelled']);
            $table->date('closing_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
