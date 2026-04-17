<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->string('value', 255);
            $table->string('label', 60)->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_identities');
    }
};