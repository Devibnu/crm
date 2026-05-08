<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->enum('platform', ['instagram', 'facebook', 'linkedin', 'twitter', 'tiktok', 'youtube']);
            $table->string('post_title');
            $table->text('content')->nullable();
            $table->string('post_url')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('impressions_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('platform');
            $table->index('status');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_engagements');
    }
};
