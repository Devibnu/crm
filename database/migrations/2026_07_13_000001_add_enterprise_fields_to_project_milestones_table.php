<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_milestones', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_milestones', 'start_date')) {
                $table->date('start_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('project_milestones', 'color')) {
                $table->string('color', 32)->default('blue')->after('description');
            }

            if (! Schema::hasColumn('project_milestones', 'icon')) {
                $table->string('icon', 64)->default('calendar')->after('color');
            }

            if (! Schema::hasColumn('project_milestones', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('sort_order')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('project_milestones', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('project_milestones', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table): void {
            if (Schema::hasColumn('project_milestones', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['updated_by', 'created_by'] as $column) {
                if (Schema::hasColumn('project_milestones', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['icon', 'color', 'start_date'] as $column) {
                if (Schema::hasColumn('project_milestones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
