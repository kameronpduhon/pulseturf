<?php

namespace Tests\Feature\Jobs;

use App\Exceptions\OutscraperException;
use App\Jobs\ScrapeCompetitorJob;
use App\Models\Competitor;
use App\Models\Review;
use App\Models\ScrapeLog;
use App\Services\OutscraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapeCompetitorJobTest extends TestCase
{
    use RefreshDatabase;

    private function mockService(array $info = [], array $reviews = []): void
    {
        $defaultInfo = [
            'rating' => 4.3,
            'review_count' => 89,
            'phone' => '+15125559876',
            'website' => 'https://www.competitor-spa.com',
            'categories' => ['Med Spa'],
            'hours' => ['Monday' => '10 AM - 5 PM'],
        ];

        $defaultReviews = [
            [
                'google_review_id' => 'comp-review-1',
                'author_name' => 'Jane Doe',
                'author_image' => null,
                'rating' => 5,
                'text' => 'Love this place!',
                'published_at' => '2026-02-20 10:00:00',
                'owner_response' => null,
                'owner_response_at' => null,
            ],
            [
                'google_review_id' => 'comp-review-2',
                'author_name' => 'John Smith',
                'author_image' => null,
                'rating' => 3,
                'text' => 'It was okay.',
                'published_at' => '2026-02-18 15:30:00',
                'owner_response' => 'Thanks for the feedback!',
                'owner_response_at' => '2026-02-19 09:00:00',
            ],
        ];

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->andReturn(array_merge($defaultInfo, $info));
        $mock->shouldReceive('getReviews')
            ->once()
            ->andReturn(empty($reviews) && empty(func_get_args()[1] ?? null) ? $defaultReviews : $reviews);
    }

    public function test_updates_competitor_profile(): void
    {
        $competitor = Competitor::factory()->create([
            'google_place_id' => 'ChIJcomp123',
            'google_rating' => 4.0,
            'google_review_count' => 50,
        ]);

        $this->mockService();

        (new ScrapeCompetitorJob($competitor))->handle(app(OutscraperService::class));

        $competitor->refresh();
        $this->assertEquals('4.3', $competitor->google_rating);
        $this->assertEquals(89, $competitor->google_review_count);
        $this->assertEquals('+15125559876', $competitor->phone);
        $this->assertEquals('https://www.competitor-spa.com', $competitor->website);
        $this->assertNotNull($competitor->last_scraped_at);
    }

    public function test_upserts_reviews_without_duplicates(): void
    {
        $competitor = Competitor::factory()->create([
            'google_place_id' => 'ChIJcomp123',
        ]);

        Review::factory()->create([
            'reviewable_type' => Competitor::class,
            'reviewable_id' => $competitor->id,
            'google_review_id' => 'comp-review-1',
            'author_name' => 'Old Name',
        ]);

        $this->mockService();

        (new ScrapeCompetitorJob($competitor))->handle(app(OutscraperService::class));

        $this->assertEquals(2, $competitor->reviews()->count());

        $updated = $competitor->reviews()->where('google_review_id', 'comp-review-1')->first();
        $this->assertEquals('Jane Doe', $updated->author_name);
    }

    public function test_creates_success_scrape_log(): void
    {
        $competitor = Competitor::factory()->create([
            'google_place_id' => 'ChIJcomp123',
        ]);

        $this->mockService();

        (new ScrapeCompetitorJob($competitor))->handle(app(OutscraperService::class));

        $log = $competitor->scrapeLogs()->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('outscraper', $log->source);
        $this->assertEquals(200, $log->api_response_code);
        $this->assertEquals(2, $log->reviews_found);
        $this->assertEquals(2, $log->new_reviews);
        $this->assertEquals('4.3', $log->rating_at_scrape);
        $this->assertEquals(89, $log->review_count_at_scrape);
    }

    public function test_creates_failed_scrape_log_on_exception(): void
    {
        $competitor = Competitor::factory()->create([
            'google_place_id' => 'ChIJcomp123',
        ]);

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->andThrow(new OutscraperException('API failed', 500, 'Server Error'));

        $job = new ScrapeCompetitorJob($competitor);

        try {
            $job->handle(app(OutscraperService::class));
        } catch (OutscraperException) {
            // Expected
        }

        $job->failed(new OutscraperException('API failed', 500, 'Server Error'));

        $log = $competitor->scrapeLogs()->first();
        $this->assertNotNull($log);
        $this->assertEquals('failed', $log->status);
        $this->assertEquals('API failed', $log->error_message);
        $this->assertEquals(500, $log->api_response_code);
    }
}
