<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('scrapeable');
            $table->string('status', 20)->default('pending');
            $table->string('source', 50)->default('outscraper');
            $table->unsignedInteger('api_response_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('reviews_found')->nullable();
            $table->unsignedInteger('new_reviews')->nullable();
            $table->decimal('rating_at_scrape', 2, 1)->nullable();
            $table->unsignedInteger('review_count_at_scrape')->nullable();
            $table->unsignedInteger('cost_cents')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['scrapeable_type', 'scrapeable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_logs');
    }
};
