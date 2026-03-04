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

        if (empty($results) || empty($results[0])) {
            throw OutscraperException::noResults($query);
        }

        $business = $results[0];

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

        $reviewsData = $data[0]['reviews_data'] ?? [];

        if (empty($reviewsData)) {
            throw OutscraperException::noResults($placeId);
        }

        return array_map(fn (array $review) => [
            'google_review_id' => $review['review_link'] ?? $review['google_id'] ?? null,
            'author_name' => $review['autor_name'] ?? null,
            'author_image' => $review['autor_image'] ?? null,
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

        if (empty($results) || empty($results[0])) {
            throw OutscraperException::noResults($placeId);
        }

        $business = $results[0];

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
