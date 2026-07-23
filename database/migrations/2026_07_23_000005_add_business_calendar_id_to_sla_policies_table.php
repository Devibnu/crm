<?php

use App\Models\BusinessCalendar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sla_policies', function (Blueprint $table) {
            $table->foreignId('business_calendar_id')
                ->nullable()
                ->after('description')
                ->constrained('business_calendars')
                ->nullOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_business_calendar_id')
                ->nullable()
                ->after('sla_policy_id')
                ->constrained('business_calendars')
                ->nullOnDelete();
        });

        $defaultCalendarId = BusinessCalendar::query()
            ->defaultCalendar()
            ->value('id');

        if ($defaultCalendarId) {
            DB::table('sla_policies')
                ->whereNull('business_calendar_id')
                ->update(['business_calendar_id' => $defaultCalendarId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sla_business_calendar_id');
        });

        Schema::table('sla_policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_calendar_id');
        });
    }
};
