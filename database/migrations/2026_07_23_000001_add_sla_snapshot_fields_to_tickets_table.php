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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->unsignedInteger('sla_response_time_minutes')->nullable();
            $table->unsignedInteger('sla_resolution_time_minutes')->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('sla_response_breached_at')->nullable();
            $table->timestamp('sla_resolution_breached_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sla_policy_id');
            $table->dropColumn([
                'sla_response_time_minutes',
                'sla_resolution_time_minutes',
                'response_due_at',
                'resolution_due_at',
                'first_responded_at',
                'sla_response_breached_at',
                'sla_resolution_breached_at',
            ]);
        });
    }
};
