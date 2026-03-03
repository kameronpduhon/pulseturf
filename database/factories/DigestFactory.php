<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Digest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Digest>
 */
class DigestFactory extends Factory
{
    protected $model = Digest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_id' => Business::factory(),
            'week_start' => now()->startOfWeek()->subWeeks(fake()->numberBetween(0, 4)),
            'subject_line' => 'Your Weekly Med Spa Intelligence Report',
            'html_content' => '<html><body><h1>Weekly Digest</h1><p>Here is your weekly competitive intelligence report.</p></body></html>',
            'plain_content' => 'Weekly Digest - Here is your weekly competitive intelligence report.',
            'llm_prompt' => null,
            'llm_response' => null,
            'llm_model' => 'gpt-4o-mini',
            'llm_tokens_used' => fake()->numberBetween(500, 2000),
            'llm_cost_cents' => fake()->numberBetween(1, 5),
            'status' => 'draft',
            'sent_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'sent_at' => null,
        ]);
    }
}
