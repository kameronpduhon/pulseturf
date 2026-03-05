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

    public function generated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => 'generated',
            'subject_line'    => 'Your Weekly Med Spa Intel: Rating Update',
            'html_content'    => '<h2>Performance Snapshot</h2><p>Your business is doing great this week.</p>',
            'content_json'    => [
                'performance_snapshot' => 'Your business maintained a strong 4.8-star rating this week.',
                'review_highlights' => 'Top Review (5/5): "Amazing experience!" — Sarah M.',
                'competitor_watch' => 'Glow Med Spa: 4.2 stars (89 total reviews).',
                'sentiment_trends' => '2 positive reviews, 0 critical reviews this week.',
                'action_items' => "• Respond to Sarah M.'s review.\n• Ask satisfied clients for reviews.",
                'week_ahead' => 'Focus on converting satisfied clients into reviewers this week.',
            ],
            'llm_model'       => 'gpt-4o-mini',
            'llm_prompt'      => 'Test prompt',
            'llm_response'    => '{"subject_line": "test"}',
            'llm_tokens_used' => 1000,
            'llm_cost_cents'  => 5,
        ]);
    }

    public function withoutContentJson(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_json' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'failed',
            'llm_model'    => 'gpt-4o-mini',
        ]);
    }
}
