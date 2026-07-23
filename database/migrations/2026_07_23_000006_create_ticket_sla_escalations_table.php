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
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->unsignedTinyInteger('response_warning_percentage')->default(80)->after('response_time_minutes');
            $table->unsignedTinyInteger('resolution_warning_percentage')->default(80)->after('resolution_time_minutes');
        });

        Schema::create('ticket_sla_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->timestamp('triggered_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('status');
            $table->index('type');
            $table->unique(['ticket_id', 'type'], 'ticket_sla_escalations_ticket_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sla_escalations');

        Schema::table('sla_policies', function (Blueprint $table) {
            $table->dropColumn([
                'response_warning_percentage',
                'resolution_warning_percentage',
            ]);
        });
    }
};
