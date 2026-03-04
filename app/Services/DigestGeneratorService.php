<?php

namespace App\Services;

use App\Exceptions\OpenAIException;
use App\Models\Business;
use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigestGeneratorService
{
    private const BASE_URL = 'https://api.openai.com/v1';
    private const MODEL = 'gpt-4o-mini';
    private const MAX_TOKENS = 1500;

    private const ALLOWED_TAGS = '<h1><h2><h3><h4><h5><h6><p><br><ul><ol><li><strong><b><em><i><a><table><thead><tbody><tr><th><td><hr><blockquote><span>';

    public function __construct() {}

    public function generate(Business $business): DigestResult
    {
        try {
            $data = $this->gatherData($business);
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($data);

            $response = $this->callOpenAI($systemPrompt, $userPrompt);
            [$subjectLine, $body] = $this->parseResponse($response);

            $usage = $response['usage'] ?? [];
            $tokensUsed = $usage['total_tokens'] ?? null;
            $costCents = $this->estimateCostCents($usage['prompt_tokens'] ?? 0, $usage['completion_tokens'] ?? 0);

            return new DigestResult(
                subjectLine: $subjectLine,
                content: $body,
                prompt: $userPrompt,
                rawResponse: json_encode($response),
                model: $response['model'] ?? self::MODEL,
                tokensUsed: $tokensUsed,
                costCents: $costCents,
                isFallback: false,
            );
        } catch (OpenAIException $e) {
            Log::warning('DigestGeneratorService: OpenAI call failed, using fallback.', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return $this->generateFallback($business);
        }
    }

    private function gatherData(Business $business): array
    {
        $business->load([
            'reviews' => fn ($q) => $q->recent(7)->orderByDesc('published_at'),
            'competitors.reviews' => fn ($q) => $q->recent(7)->orderByDesc('published_at'),
        ]);

        $previousDigest = $business->digests()
            ->where('week_start', '<', now()->startOfWeek()->toDateString())
            ->latest('week_start')
            ->first();

        $recentReviews = $business->reviews->filter(fn (Review $r) => $r->published_at >= now()->subDays(7));
        $previousReviews = $business->reviews()->where('published_at', '<', now()->subDays(7))->where('published_at', '>=', now()->subDays(14))->get();

        $ratingDelta = null;
        $reviewCountDelta = null;

        if ($previousReviews->isNotEmpty()) {
            $previousAvgRating = $previousReviews->avg('rating');
            $currentAvgRating = $recentReviews->avg('rating');
            if ($previousAvgRating && $currentAvgRating) {
                $ratingDelta = round($currentAvgRating - $previousAvgRating, 2);
            }
            $reviewCountDelta = $recentReviews->count() - $previousReviews->count();
        }

        return [
            'business' => [
                'name' => $business->name,
                'address' => $business->address,
                'google_rating' => $business->google_rating,
                'google_review_count' => $business->google_review_count,
                'last_scraped_at' => $business->last_scraped_at?->toDateTimeString(),
            ],
            'recent_reviews' => $recentReviews->map(fn (Review $r) => [
                'author' => $r->author_name,
                'rating' => $r->rating,
                'text' => $r->text ? mb_substr($r->text, 0, 300) : null,
                'published_at' => $r->published_at?->toDateString(),
                'sentiment' => $r->sentiment,
            ])->values()->toArray(),
            'competitors' => $business->competitors->map(function ($competitor) {
                $recentCompReviews = $competitor->reviews->filter(
                    fn (Review $r) => $r->published_at >= now()->subDays(7)
                );

                return [
                    'name' => $competitor->name,
                    'google_rating' => $competitor->google_rating,
                    'google_review_count' => $competitor->google_review_count,
                    'recent_review_count' => $recentCompReviews->count(),
                    'recent_reviews' => $recentCompReviews->take(3)->map(fn (Review $r) => [
                        'rating' => $r->rating,
                        'text' => $r->text ? mb_substr($r->text, 0, 200) : null,
                    ])->values()->toArray(),
                ];
            })->toArray(),
            'deltas' => [
                'rating_change' => $ratingDelta,
                'review_count_change' => $reviewCountDelta,
                'recent_review_count' => $recentReviews->count(),
                'positive_review_count' => $recentReviews->filter(fn (Review $r) => $r->rating >= 4)->count(),
                'negative_review_count' => $recentReviews->filter(fn (Review $r) => $r->rating <= 2)->count(),
            ],
            'week_start' => now()->startOfWeek()->toDateString(),
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a sharp, data-driven marketing analyst who specializes in helping med spa owners grow their businesses. Your weekly intelligence digest is concise, actionable, and uses plain language that busy owners can act on immediately.

Your task is to analyze the provided business data and produce a weekly digest email. The digest must be returned as a single JSON object with exactly two keys:
- "subject_line": A compelling email subject line (max 60 characters) that highlights the most important insight of the week.
- "body": The full HTML body of the digest email. Use HTML tags (h2, p, ul, li, strong, em) for structure. Do not include <html>, <head>, or <body> tags — just the inner content.

The body must include these sections in order, each with an <h2> heading:
1. Performance Snapshot — Current rating, review count, and week-over-week changes
2. Review Highlights — Quote the best and/or most critical recent reviews with author name and rating
3. Competitor Watch — How each competitor is performing this week, with comparisons
4. Sentiment Trends — Positive vs negative review breakdown and any notable trends
5. Action Items — 3-5 specific, prioritized recommendations the owner can act on this week
6. Week Ahead — Brief motivational note and focus area for the coming week

Tone: professional but friendly, data-informed, never alarmist. If there is no data for a section, say so briefly.

CRITICAL: Return ONLY valid JSON. No markdown fences, no extra text, no explanation outside the JSON object.
PROMPT;
    }

    private function buildUserPrompt(array $data): string
    {
        return "Here is the business intelligence data for this week. Analyze it and produce the weekly digest JSON:\n\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function callOpenAI(string $systemPrompt, string $userPrompt): array
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            throw OpenAIException::missingApiKey();
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post(self::BASE_URL . '/chat/completions', [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'temperature' => 0.7,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if ($response->status() === 429) {
            throw OpenAIException::rateLimited($response->body());
        }

        if (! $response->successful()) {
            throw OpenAIException::apiError($response->status(), $response->body());
        }

        return $response->json();
    }

    private function parseResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (empty($content)) {
            throw OpenAIException::malformedResponse('Empty content in response choices');
        }

        // Strip markdown code fences if the LLM wrapped the JSON
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw OpenAIException::malformedResponse('Invalid JSON: ' . json_last_error_msg());
        }

        $subjectLine = $decoded['subject_line'] ?? null;
        $body = $decoded['body'] ?? null;

        if (empty($subjectLine)) {
            throw OpenAIException::malformedResponse('Missing subject_line key in JSON response');
        }

        if (empty($body)) {
            throw OpenAIException::malformedResponse('Missing body key in JSON response');
        }

        // Sanitize HTML to prevent stored XSS from AI output
        $body = strip_tags($body, self::ALLOWED_TAGS);

        return [$subjectLine, $body];
    }

    private function generateFallback(Business $business): DigestResult
    {
        $business->loadMissing([
            'competitors',
            'reviews' => fn ($q) => $q->recent(7)->orderByDesc('published_at'),
        ]);

        $recentReviews = $business->reviews->filter(fn (Review $r) => $r->published_at >= now()->subDays(7));
        $positiveReviews = $recentReviews->filter(fn (Review $r) => $r->rating >= 4);
        $negativeReviews = $recentReviews->filter(fn (Review $r) => $r->rating <= 2);
        $topReview = $positiveReviews->sortByDesc('rating')->first();
        $criticalReview = $negativeReviews->sortBy('rating')->first();

        $weekStart = now()->startOfWeek()->format('M j, Y');
        $subjectLine = "Your Weekly Med Spa Intel — {$weekStart}";

        $reviewHighlights = '';
        if ($topReview) {
            $reviewHighlights .= '<p><strong>Top Review (' . $topReview->rating . '/5):</strong> ';
            if ($topReview->text) {
                $reviewHighlights .= '"' . e(mb_substr($topReview->text, 0, 200)) . '"';
            }
            if ($topReview->author_name) {
                $reviewHighlights .= ' — ' . e($topReview->author_name);
            }
            $reviewHighlights .= '</p>';
        }
        if ($criticalReview) {
            $reviewHighlights .= '<p><strong>Review Needing Attention (' . $criticalReview->rating . '/5):</strong> ';
            if ($criticalReview->text) {
                $reviewHighlights .= '"' . e(mb_substr($criticalReview->text, 0, 200)) . '"';
            }
            $reviewHighlights .= '</p>';
        }
        if (! $topReview && ! $criticalReview) {
            $reviewHighlights = '<p>No new reviews this week.</p>';
        }

        $competitorHtml = '';
        foreach ($business->competitors as $competitor) {
            $competitorHtml .= '<li>';
            $competitorHtml .= '<strong>' . e($competitor->name) . '</strong>: ';
            $competitorHtml .= $competitor->google_rating ? $competitor->google_rating . ' stars' : 'No rating data';
            if ($competitor->google_review_count) {
                $competitorHtml .= ' (' . number_format($competitor->google_review_count) . ' total reviews)';
            }
            $competitorHtml .= '</li>';
        }
        $competitorSection = $competitorHtml
            ? '<ul>' . $competitorHtml . '</ul>'
            : '<p>No competitor data available.</p>';

        $body = <<<HTML
<h2>Performance Snapshot</h2>
<p>
    <strong>Current Rating:</strong> {$business->google_rating} &#9733;<br>
    <strong>Total Reviews:</strong> {$business->google_review_count}<br>
    <strong>New Reviews This Week:</strong> {$recentReviews->count()}<br>
    <strong>Positive (4-5&#9733;):</strong> {$positiveReviews->count()} &nbsp;|&nbsp; <strong>Critical (1-2&#9733;):</strong> {$negativeReviews->count()}
</p>

<h2>Review Highlights</h2>
{$reviewHighlights}

<h2>Competitor Watch</h2>
{$competitorSection}

<h2>Sentiment Trends</h2>
<p>
    This week you received <strong>{$recentReviews->count()} new reviews</strong>.
    {$positiveReviews->count()} were positive (4+ stars) and {$negativeReviews->count()} were critical (2 stars or fewer).
</p>

<h2>Action Items</h2>
<ul>
    <li>Respond to any unanswered reviews from this week to boost your engagement score.</li>
    <li>Share a positive review on your social media to attract new clients.</li>
    <li>Follow up with clients who left critical reviews to understand their concerns.</li>
    <li>Check competitor pricing or services mentioned in recent reviews.</li>
</ul>

<h2>Week Ahead</h2>
<p>Keep delivering exceptional experiences. Every 5-star review strengthens your competitive position — your clients are your best marketing team. Focus on requesting reviews from satisfied clients this week.</p>
HTML;

        return new DigestResult(
            subjectLine: $subjectLine,
            content: $body,
            prompt: null,
            rawResponse: null,
            model: null,
            tokensUsed: null,
            costCents: null,
            isFallback: true,
        );
    }

    private function estimateCostCents(int $promptTokens, int $completionTokens): int
    {
        // gpt-4o-mini pricing: $0.15 per 1M input tokens, $0.60 per 1M output tokens
        $inputCost = ($promptTokens / 1_000_000) * 0.15;
        $outputCost = ($completionTokens / 1_000_000) * 0.60;

        return (int) round(($inputCost + $outputCost) * 100);
    }
}
