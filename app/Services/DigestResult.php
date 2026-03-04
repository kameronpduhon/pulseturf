<?php

namespace App\Services;

class DigestResult
{
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $content,
        public readonly ?string $prompt,
        public readonly ?string $rawResponse,
        public readonly ?string $model,
        public readonly ?int $tokensUsed,
        public readonly ?int $costCents,
        public readonly bool $isFallback = false,
    ) {}
}
