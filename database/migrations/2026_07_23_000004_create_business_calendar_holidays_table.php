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
        Schema::create('business_calendar_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_calendar_id')->constrained('business_calendars')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->unique(['business_calendar_id', 'holiday_date'], 'business_calendar_holiday_date_unique');
            $table->index(['business_calendar_id', 'is_recurring']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_calendar_holidays');
    }
};
