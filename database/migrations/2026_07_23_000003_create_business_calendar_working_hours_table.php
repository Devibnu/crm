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
        Schema::create('business_calendar_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_calendar_id')->constrained('business_calendars')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_working_day')->default(true);
            $table->timestamps();

            $table->unique(['business_calendar_id', 'day_of_week'], 'business_calendar_weekday_unique');
            $table->index(['business_calendar_id', 'is_working_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_calendar_working_hours');
    }
};
