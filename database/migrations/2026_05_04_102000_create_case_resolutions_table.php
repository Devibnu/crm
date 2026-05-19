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
        Schema::create('case_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('resolution_summary');
            $table->text('resolution_notes')->nullable();
            $table->text('root_cause')->nullable();
            $table->enum('resolution_type', ['workaround', 'fixed', 'duplicate', 'invalid', 'escalated'])->default('fixed');
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('customer_notified')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_resolutions');
    }
};
