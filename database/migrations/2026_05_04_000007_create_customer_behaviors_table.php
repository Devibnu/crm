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
        Schema::create('customer_behaviors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('lifecycle_stage', ['lead', 'prospect', 'active', 'loyal', 'churned'])->default('lead');
            $table->integer('engagement_score')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->string('product_interest')->nullable();
            $table->text('behavior_notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'lifecycle_stage']);
            $table->index('engagement_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_behaviors');
    }
};
