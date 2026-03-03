<?php

namespace Tests\Feature\Jobs;

use App\Exceptions\OutscraperException;
use App\Jobs\ScrapeBusinessJob;
use App\Models\Business;
use App\Models\Review;
use App\Models\ScrapeLog;
use App\Services\OutscraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapeBusinessJobTest extends TestCase
{
    use RefreshDatabase;

    private function mockService(array $info = [], array $reviews = []): void
    {
        $defaultInfo = [
            'rating' => 4.7,
            'review_count' => 134,
            'phone' => '+15125551234',
            'website' => 'https://www.glowaesthetics.com',
            'categories' => ['Med Spa', 'Skin Care Clinic'],
            'hours' => ['Monday' => '9 AM - 6 PM'],
        ];

        $defaultReviews = [
            [
                'google_review_id' => 'review-1',
                'author_name' => 'Sarah Johnson',
                'author_image' => 'https://example.com/photo1.jpg',
                'rating' => 5,
                'text' => 'Amazing experience!',
                'published_at' => '2026-02-15 14:30:00',
                'owner_response' => null,
                'owner_response_at' => null,
            ],
            [
                'google_review_id' => 'review-2',
                'author_name' => 'Michael Chen',
                'author_image' => null,
                'rating' => 4,
                'text' => 'Great service.',
                'published_at' => '2026-02-10 09:15:00',
                'owner_response' => 'Thank you!',
                'owner_response_at' => '2026-02-11 11:00:00',
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

    public function test_updates_business_profile(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
            'google_rating' => 4.5,
            'google_review_count' => 100,
        ]);

        $this->mockService();

        (new ScrapeBusinessJob($business))->handle(app(OutscraperService::class));

        $business->refresh();
        $this->assertEquals('4.7', $business->google_rating);
        $this->assertEquals(134, $business->google_review_count);
        $this->assertEquals('+15125551234', $business->phone);
        $this->assertEquals('https://www.glowaesthetics.com', $business->website);
        $this->assertEquals(['Med Spa', 'Skin Care Clinic'], $business->google_categories);
        $this->assertNotNull($business->last_scraped_at);
    }

    public function test_upserts_reviews_without_duplicates(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
        ]);

        // Pre-create a review with the same google_review_id
        Review::factory()->create([
            'reviewable_type' => Business::class,
            'reviewable_id' => $business->id,
            'google_review_id' => 'review-1',
            'author_name' => 'Old Name',
            'rating' => 3,
        ]);

        $this->mockService();

        (new ScrapeBusinessJob($business))->handle(app(OutscraperService::class));

        // Should have 2 reviews total (1 updated + 1 new), not 3
        $this->assertEquals(2, $business->reviews()->count());

        // The existing review should be updated
        $updated = $business->reviews()->where('google_review_id', 'review-1')->first();
        $this->assertEquals('Sarah Johnson', $updated->author_name);
        $this->assertEquals(5, $updated->rating);
    }

    public function test_creates_success_scrape_log(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
        ]);

        $this->mockService();

        (new ScrapeBusinessJob($business))->handle(app(OutscraperService::class));

        $log = $business->scrapeLogs()->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('outscraper', $log->source);
        $this->assertEquals(200, $log->api_response_code);
        $this->assertEquals(2, $log->reviews_found);
        $this->assertEquals(2, $log->new_reviews);
        $this->assertEquals('4.7', $log->rating_at_scrape);
        $this->assertEquals(134, $log->review_count_at_scrape);
        $this->assertNotNull($log->duration_ms);
    }

    public function test_activates_pending_business(): void
    {
        $business = Business::factory()->create([
            'status' => 'pending_setup',
            'google_place_id' => 'ChIJtest123',
        ]);

        $this->mockService();

        (new ScrapeBusinessJob($business))->handle(app(OutscraperService::class));

        $this->assertEquals('active', $business->fresh()->status);
    }

    public function test_does_not_change_active_status(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
        ]);

        $this->mockService();

        (new ScrapeBusinessJob($business))->handle(app(OutscraperService::class));

        $this->assertEquals('active', $business->fresh()->status);
    }

    public function test_creates_failed_scrape_log_on_exception(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
        ]);

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->andThrow(new OutscraperException('API failed', 500, 'Server Error'));

        $job = new ScrapeBusinessJob($business);

        try {
            $job->handle(app(OutscraperService::class));
        } catch (OutscraperException) {
            // Expected — the job would normally be retried by the queue
        }

        // Simulate Laravel calling failed() after retries exhausted
        $job->failed(new OutscraperException('API failed', 500, 'Server Error'));

        $log = $business->scrapeLogs()->first();
        $this->assertNotNull($log);
        $this->assertEquals('failed', $log->status);
        $this->assertEquals('API failed', $log->error_message);
        $this->assertEquals(500, $log->api_response_code);
    }

    public function test_fails_immediately_on_4xx(): void
    {
        $business = Business::factory()->active()->create([
            'google_place_id' => 'ChIJtest123',
        ]);

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->andThrow(new OutscraperException('Unprocessable', 422, '{"error":"bad request"}'));

        $job = new ScrapeBusinessJob($business);

        // On 4xx, the job catches the exception, creates a failed log, and calls $this->fail()
        // Since we're not in a real queue, fail() will throw the exception
        try {
            $job->handle(app(OutscraperService::class));
        } catch (\Throwable) {
            // Expected
        }

        $log = $business->scrapeLogs()->where('status', 'failed')->first();
        $this->assertNotNull($log);
        $this->assertEquals(422, $log->api_response_code);
        $this->assertEquals('Unprocessable', $log->error_message);
    }
}
