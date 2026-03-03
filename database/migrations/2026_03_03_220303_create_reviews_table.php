<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->string('google_review_id')->unique();
            $table->string('author_name');
            $table->string('author_image', 500)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->timestamp('published_at');
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_response_at')->nullable();
            $table->string('sentiment', 20)->nullable();
            $table->json('sentiment_topics')->nullable();
            $table->timestamps();

            $table->index(['reviewable_type', 'reviewable_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
