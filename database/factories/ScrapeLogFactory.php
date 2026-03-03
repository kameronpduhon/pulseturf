<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\ScrapeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScrapeLog>
 */
class ScrapeLogFactory extends Factory
{
    protected $model = ScrapeLog::class;

    public function definition(): array
    {
        return [
            'scrapeable_type' => Business::class,
            'scrapeable_id' => Business::factory(),
            'status' => 'success',
            'source' => 'outscraper',
            'api_response_code' => 200,
            'error_message' => null,
            'reviews_found' => fake()->numberBetween(5, 50),
            'new_reviews' => fake()->numberBetween(0, 10),
            'rating_at_scrape' => fake()->randomFloat(1, 3.5, 5.0),
            'review_count_at_scrape' => fake()->numberBetween(10, 300),
            'cost_cents' => fake()->numberBetween(1, 10),
            'duration_ms' => fake()->numberBetween(500, 5000),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'api_response_code' => 200,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'api_response_code' => 500,
            'error_message' => 'API request timed out after 30000ms',
            'reviews_found' => null,
            'new_reviews' => null,
        ]);
    }
}
