<?php

namespace Tests\Feature\Services;

use App\Exceptions\OutscraperException;
use App\Services\OutscraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutscraperServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $name): array
    {
        return json_decode(
            file_get_contents(base_path("tests/Fixtures/outscraper/{$name}.json")),
            true,
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.outscraper.key' => 'test-api-key']);
    }

    public function test_search_business_returns_mapped_profile(): void
    {
        Http::fake([
            'api.app.outscraper.com/maps/search-v3*' => Http::response($this->fixture('search_business_success')),
        ]);

        $service = app(OutscraperService::class);
        $result = $service->searchBusiness('Glow Aesthetics', '1234 Main St, Austin, TX');

        $this->assertEquals('ChIJN1t_tDeuEmsRUsoyG83frY4', $result['place_id']);
        $this->assertEquals('Glow Aesthetics', $result['name']);
        $this->assertEquals('1234 Main St, Austin, TX 78701', $result['address']);
        $this->assertEquals('+15125551234', $result['phone']);
        $this->assertEquals('https://www.glowaesthetics.com', $result['website']);
        $this->assertEquals(4.8, $result['rating']);
        $this->assertEquals(127, $result['review_count']);
        $this->assertEquals(['Med Spa', 'Skin Care Clinic', 'Beauty Salon'], $result['categories']);
        $this->assertIsArray($result['hours']);
    }

    public function test_search_business_throws_on_no_results(): void
    {
        Http::fake([
            'api.app.outscraper.com/maps/search-v3*' => Http::response($this->fixture('no_results')),
        ]);

        $this->expectException(OutscraperException::class);
        $this->expectExceptionMessage('no results');

        $service = app(OutscraperService::class);
        $service->searchBusiness('Nonexistent Spa', '999 Fake St');
    }

    public function test_get_reviews_returns_mapped_reviews(): void
    {
        Http::fake([
            'api.app.outscraper.com/maps/reviews-v3*' => Http::response($this->fixture('get_reviews_success')),
        ]);

        $service = app(OutscraperService::class);
        $reviews = $service->getReviews('ChIJN1t_tDeuEmsRUsoyG83frY4');

        $this->assertCount(3, $reviews);

        // First review — 5 star, no owner response
        $this->assertEquals('Sarah Johnson', $reviews[0]['author_name']);
        $this->assertEquals('https://lh3.googleusercontent.com/a/photo1.jpg', $reviews[0]['author_image']);
        $this->assertEquals(5, $reviews[0]['rating']);
        $this->assertStringContains('Amazing experience', $reviews[0]['text']);
        $this->assertEquals('2026-02-15 14:30:00', $reviews[0]['published_at']);
        $this->assertNull($reviews[0]['owner_response']);

        // Second review — has owner response
        $this->assertEquals('Michael Chen', $reviews[1]['author_name']);
        $this->assertEquals(4, $reviews[1]['rating']);
        $this->assertNotNull($reviews[1]['owner_response']);
        $this->assertEquals('2026-02-11 11:00:00', $reviews[1]['owner_response_at']);

        // Third review — low rating
        $this->assertEquals('Emily Rodriguez', $reviews[2]['author_name']);
        $this->assertEquals(2, $reviews[2]['rating']);
    }

    public function test_get_business_info_returns_profile(): void
    {
        Http::fake([
            'api.app.outscraper.com/maps/search-v3*' => Http::response($this->fixture('get_business_info_success')),
        ]);

        $service = app(OutscraperService::class);
        $info = $service->getBusinessInfo('ChIJN1t_tDeuEmsRUsoyG83frY4');

        $this->assertEquals(4.7, $info['rating']);
        $this->assertEquals(134, $info['review_count']);
        $this->assertEquals('+15125551234', $info['phone']);
        $this->assertEquals('https://www.glowaesthetics.com', $info['website']);
        $this->assertEquals(['Med Spa', 'Skin Care Clinic'], $info['categories']);
        $this->assertIsArray($info['hours']);
    }

    public function test_throws_on_api_error(): void
    {
        Http::fake([
            'api.app.outscraper.com/*' => Http::response($this->fixture('error_500'), 500),
        ]);

        try {
            $service = app(OutscraperService::class);
            $service->searchBusiness('Test', 'Test');
            $this->fail('Expected OutscraperException was not thrown');
        } catch (OutscraperException $e) {
            $this->assertEquals(500, $e->statusCode);
            $this->assertStringContains('HTTP 500', $e->getMessage());
        }
    }

    public function test_throws_on_missing_api_key(): void
    {
        config(['services.outscraper.key' => null]);

        $this->expectException(OutscraperException::class);
        $this->expectExceptionMessage('API key is not configured');

        app(OutscraperService::class);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'",
        );
    }
}
