<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('governance_level');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('reference_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reference_type_id')->constrained('reference_types')->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reference_type_id', 'code']);
            $table->index(['reference_type_id', 'is_active', 'sort_order']);
        });

        Schema::create('reference_value_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reference_value_id')->constrained('reference_values')->cascadeOnDelete();
            $table->string('capability');
            $table->timestamps();

            $table->unique(['reference_value_id', 'capability']);
            $table->index('capability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_value_capabilities');
        Schema::dropIfExists('reference_values');
        Schema::dropIfExists('reference_types');
    }
};
