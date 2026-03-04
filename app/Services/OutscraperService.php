<?php

namespace App\Services;

use App\Exceptions\OutscraperException;
use Illuminate\Support\Facades\Http;

class OutscraperService
{
    private string $apiKey;
    private const BASE_URL = 'https://api.app.outscraper.com';

    public function __construct()
    {
        $key = config('services.outscraper.key');

        if (empty($key)) {
            throw OutscraperException::missingApiKey();
        }

        $this->apiKey = $key;
    }

    public function searchBusiness(string $name, string $address): array
    {
        $query = "{$name} {$address}";
        $response = $this->request('/maps/search-v3', [
            'query' => $query,
            'limit' => 1,
            'async' => 'false',
        ]);

        $results = $response['data'] ?? [];

        // Outscraper search-v3 returns data[0][0] when async=false
        $firstResult = $results[0] ?? [];
        $business = isset($firstResult[0]) ? $firstResult[0] : $firstResult;

        if (empty($business)) {
            throw OutscraperException::noResults($query);
        }

        return [
            'place_id' => $business['place_id'] ?? null,
            'name' => $business['name'] ?? null,
            'address' => $business['full_address'] ?? null,
            'phone' => $business['phone'] ?? null,
            'website' => $business['site'] ?? null,
            'rating' => $business['rating'] ?? null,
            'review_count' => $business['reviews'] ?? null,
            'categories' => $business['subtypes'] ?? [],
            'hours' => $business['working_hours'] ?? null,
        ];
    }

    public function getReviews(string $placeId, int $limit = 20): array
    {
        $response = $this->request('/maps/reviews-v3', [
            'query' => $placeId,
            'reviewsLimit' => $limit,
            'sort' => 'newest',
            'async' => 'false',
        ]);

        $data = $response['data'] ?? [];

        if (empty($data) || empty($data[0])) {
            throw OutscraperException::noResults($placeId);
        }

        // Handle double-nested structure when async=false
        $firstResult = $data[0];
        $businessData = isset($firstResult[0]) ? $firstResult[0] : $firstResult;
        $reviewsData = $businessData['reviews_data'] ?? [];

        if (empty($reviewsData)) {
            throw OutscraperException::noResults($placeId);
        }

        return array_map(fn (array $review) => [
            'google_review_id' => $review['review_link'] ?? $review['review_id'] ?? null,
            'author_name' => $review['author_title'] ?? null,
            'author_image' => $review['author_image'] ?? null,
            'rating' => $review['review_rating'] ?? null,
            'text' => $review['review_text'] ?? null,
            'published_at' => $review['review_datetime_utc'] ?? null,
            'owner_response' => $review['owner_answer'] ?? null,
            'owner_response_at' => $review['owner_answer_timestamp_datetime_utc'] ?? null,
        ], $reviewsData);
    }

    public function getBusinessInfo(string $placeId): array
    {
        $response = $this->request('/maps/search-v3', [
            'query' => $placeId,
            'limit' => 1,
            'async' => 'false',
        ]);

        $results = $response['data'] ?? [];

        $firstResult = $results[0] ?? [];
        $business = isset($firstResult[0]) ? $firstResult[0] : $firstResult;

        if (empty($business)) {
            throw OutscraperException::noResults($placeId);
        }

        return [
            'rating' => $business['rating'] ?? null,
            'review_count' => $business['reviews'] ?? null,
            'phone' => $business['phone'] ?? null,
            'website' => $business['site'] ?? null,
            'categories' => $business['subtypes'] ?? [],
            'hours' => $business['working_hours'] ?? null,
        ];
    }

    private function request(string $endpoint, array $params): array
    {
        $response = Http::withHeaders(['X-API-KEY' => $this->apiKey])
            ->timeout(30)
            ->get(self::BASE_URL . $endpoint, $params);

        if (! $response->successful()) {
            throw OutscraperException::apiError($response->status(), $response->body());
        }

        return $response->json();
    }
}
