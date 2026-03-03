<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->string('subject_line');
            $table->longText('html_content');
            $table->longText('plain_content')->nullable();
            $table->text('llm_prompt')->nullable();
            $table->text('llm_response')->nullable();
            $table->string('llm_model', 50)->nullable();
            $table->unsignedInteger('llm_tokens_used')->nullable();
            $table->unsignedInteger('llm_cost_cents')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'week_start']);
            $table->unique(['business_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digests');
    }
};
