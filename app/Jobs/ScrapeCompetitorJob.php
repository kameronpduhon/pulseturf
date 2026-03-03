<?php

namespace App\Jobs;

use App\Exceptions\OutscraperException;
use App\Models\Competitor;
use App\Services\OutscraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeCompetitorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 1800];
    public $timeout = 120;

    public function __construct(public Competitor $competitor) {}

    public function handle(OutscraperService $service): void
    {
        $startTime = microtime(true);

        try {
            $info = $service->getBusinessInfo($this->competitor->google_place_id);
            $reviews = $service->getReviews($this->competitor->google_place_id, 20);
        } catch (OutscraperException $e) {
            if ($e->statusCode !== null && $e->statusCode >= 400 && $e->statusCode < 500) {
                $this->createFailedLog($e, $startTime);
                $this->fail($e);

                return;
            }

            throw $e;
        }

        $this->competitor->update([
            'google_rating' => $info['rating'],
            'google_review_count' => $info['review_count'],
            'phone' => $info['phone'],
            'website' => $info['website'],
            'google_categories' => $info['categories'],
            'google_hours' => $info['hours'],
            'last_scraped_at' => now(),
        ]);

        $newCount = 0;
        foreach ($reviews as $review) {
            $wasRecentlyCreated = $this->competitor->reviews()->updateOrCreate(
                ['google_review_id' => $review['google_review_id']],
                [
                    'author_name' => $review['author_name'],
                    'author_image' => $review['author_image'],
                    'rating' => $review['rating'],
                    'text' => $review['text'],
                    'published_at' => $review['published_at'],
                    'owner_response' => $review['owner_response'],
                    'owner_response_at' => $review['owner_response_at'],
                ],
            )->wasRecentlyCreated;

            if ($wasRecentlyCreated) {
                $newCount++;
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $this->competitor->scrapeLogs()->create([
            'status' => 'success',
            'source' => 'outscraper',
            'api_response_code' => 200,
            'reviews_found' => count($reviews),
            'new_reviews' => $newCount,
            'rating_at_scrape' => $info['rating'],
            'review_count_at_scrape' => $info['review_count'],
            'duration_ms' => $durationMs,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        if (! $this->competitor->scrapeLogs()->where('created_at', '>=', now()->subMinute())->exists()) {
            $this->createFailedLog($exception);
        }
    }

    private function createFailedLog(\Throwable $exception, ?float $startTime = null): void
    {
        $durationMs = $startTime ? (int) round((microtime(true) - $startTime) * 1000) : null;

        $this->competitor->scrapeLogs()->create([
            'status' => 'failed',
            'source' => 'outscraper',
            'api_response_code' => $exception instanceof OutscraperException ? $exception->statusCode : null,
            'error_message' => $exception->getMessage(),
            'duration_ms' => $durationMs,
        ]);
    }
}
