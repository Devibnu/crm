<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_timeline_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('event_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_timeline_events');
    }
};