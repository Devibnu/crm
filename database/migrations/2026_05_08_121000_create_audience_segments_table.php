<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['demographic', 'behavioral', 'transactional', 'engagement']);
            $table->text('description')->nullable();
            $table->json('criteria')->nullable();
            $table->integer('estimated_audience')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_segments');
    }
};
