<?php

namespace App\Exceptions;

use RuntimeException;

class OpenAIException extends RuntimeException
{
    public ?int $statusCode;
    public ?string $responseBody;

    public function __construct(string $message, ?int $statusCode = null, ?string $responseBody = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public static function apiError(int $statusCode, string $responseBody): static
    {
        return new static(
            "OpenAI API returned HTTP {$statusCode}",
            $statusCode,
            $responseBody,
        );
    }

    public static function missingApiKey(): static
    {
        return new static('OpenAI API key is not configured. Set OPENAI_API_KEY in your .env file.');
    }

    public static function rateLimited(string $responseBody): static
    {
        return new static(
            'OpenAI API rate limit exceeded.',
            429,
            $responseBody,
        );
    }

    public static function malformedResponse(string $reason): static
    {
        return new static("OpenAI returned a malformed response: {$reason}");
    }
}
