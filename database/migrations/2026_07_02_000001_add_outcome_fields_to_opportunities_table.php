<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'won_at')) {
                $table->timestamp('won_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('opportunities', 'lost_at')) {
                $table->timestamp('lost_at')->nullable()->after('won_at');
            }

            if (! Schema::hasColumn('opportunities', 'lost_reason')) {
                $table->string('lost_reason')->nullable()->after('lost_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            foreach (['lost_reason', 'lost_at', 'won_at'] as $column) {
                if (Schema::hasColumn('opportunities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
