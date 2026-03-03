<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('google_place_id')->nullable();
            $table->string('address', 500);
            $table->string('city', 100);
            $table->string('state', 50);
            $table->string('zip', 10);
            $table->string('phone', 20)->nullable();
            $table->string('website', 500)->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_review_count')->nullable();
            $table->json('google_categories')->nullable();
            $table->json('google_hours')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitors');
    }
};
