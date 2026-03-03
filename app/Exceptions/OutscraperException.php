<?php

namespace App\Exceptions;

use RuntimeException;

class OutscraperException extends RuntimeException
{
    public ?int $statusCode;
    public ?string $responseBody;

    public function __construct(string $message, ?int $statusCode = null, ?string $responseBody = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public static function apiError(int $status, string $body): static
    {
        return new static(
            "Outscraper API returned HTTP {$status}",
            $status,
            $body,
        );
    }

    public static function noResults(string $query): static
    {
        return new static("Outscraper returned no results for: {$query}");
    }

    public static function missingApiKey(): static
    {
        return new static('Outscraper API key is not configured. Set OUTSCRAPER_API_KEY in your .env file.');
    }
}
