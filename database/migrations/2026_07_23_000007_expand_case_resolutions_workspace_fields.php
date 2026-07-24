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
        Schema::table('case_resolutions', function (Blueprint $table) {
            $table->text('workaround')->nullable()->after('root_cause');
            $table->text('permanent_fix')->nullable()->after('workaround');
            $table->text('internal_notes')->nullable()->after('permanent_fix');
            $table->string('resolution_outcome')->nullable()->after('resolution_type')->index();
            $table->unsignedInteger('reopened_count')->default(0)->after('resolution_outcome');
            $table->boolean('knowledge_candidate')->default(false)->after('reopened_count')->index();
            $table->foreignId('knowledge_article_id')->nullable()->after('knowledge_candidate')->constrained('knowledge_bases')->nullOnDelete();
            $table->timestamp('customer_notified_at')->nullable()->after('customer_notified');
            $table->timestamp('customer_confirmation_at')->nullable()->after('customer_notified_at');
            $table->unsignedInteger('resolution_duration_minutes')->nullable()->after('customer_confirmation_at');

            $table->index('root_cause');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_resolutions', function (Blueprint $table) {
            $table->dropForeign(['knowledge_article_id']);
            $table->dropIndex(['root_cause']);
            $table->dropIndex(['resolved_at']);
            $table->dropColumn([
                'workaround',
                'permanent_fix',
                'internal_notes',
                'resolution_outcome',
                'reopened_count',
                'knowledge_candidate',
                'knowledge_article_id',
                'customer_notified_at',
                'customer_confirmation_at',
                'resolution_duration_minutes',
            ]);
        });
    }
};
