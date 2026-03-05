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
    private const MAX_TOKENS = 2000;

    private const SECTION_KEYS = [
        'subject_line',
        'performance_snapshot',
        'review_highlights',
        'competitor_watch',
        'sentiment_trends',
        'action_items',
        'week_ahead',
    ];

    public function __construct() {}

    public function generate(Business $business): DigestResult
    {
        try {
            $data = $this->gatherData($business);
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($data);

            $response = $this->callOpenAI($systemPrompt, $userPrompt);
            [$subjectLine, $sections] = $this->parseResponse($response);

            $usage = $response['usage'] ?? [];
            $tokensUsed = $usage['total_tokens'] ?? null;
            $costCents = $this->estimateCostCents($usage['prompt_tokens'] ?? 0, $usage['completion_tokens'] ?? 0);

            $htmlContent = $this->renderHtmlContent($sections);

            return new DigestResult(
                subjectLine: $subjectLine,
                content: $htmlContent,
                contentJson: $sections,
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

Your task is to analyze the provided business data and produce a weekly digest. The digest must be returned as a single JSON object with exactly 7 keys:

- "subject_line": A compelling email subject line (max 60 characters) that highlights the most important insight of the week.
- "performance_snapshot": Current rating, review count, and week-over-week changes. Plain text, no HTML.
- "review_highlights": Quote the best and/or most critical recent reviews with author name and rating. Plain text, no HTML. Use line breaks to separate reviews.
- "competitor_watch": How each competitor is performing this week, with comparisons. Plain text, no HTML.
- "sentiment_trends": Positive vs negative review breakdown and any notable trends. Plain text, no HTML.
- "action_items": 3-5 specific, prioritized recommendations the owner can act on this week. Plain text, no HTML. One item per line, prefixed with a bullet character.
- "week_ahead": Brief motivational note and focus area for the coming week. Plain text, no HTML.

Tone: professional but friendly, data-informed, never alarmist. If there is no data for a section, say so briefly.

CRITICAL: Return ONLY valid JSON. No markdown fences, no extra text, no explanation outside the JSON object. All values must be plain text strings — no HTML tags allowed.
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

        if (empty($subjectLine)) {
            throw OpenAIException::malformedResponse('Missing subject_line key in JSON response');
        }

        // Extract section keys (everything except subject_line)
        $sectionKeys = array_diff(self::SECTION_KEYS, ['subject_line']);
        $sections = [];

        foreach ($sectionKeys as $key) {
            $value = $decoded[$key] ?? null;

            if ($value === null || $value === '') {
                throw OpenAIException::malformedResponse("Missing {$key} key in JSON response");
            }

            // Strip any HTML tags — sections must be plain text
            $sections[$key] = strip_tags((string) $value);
        }

        return [$subjectLine, $sections];
    }

    public function renderHtmlContent(array $sections): string
    {
        return view('partials.digest-email', ['sections' => $sections])->render();
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

        // Build structured sections
        $performanceSnapshot = "Current Rating: {$business->google_rating} stars\n"
            . "Total Reviews: {$business->google_review_count}\n"
            . "New Reviews This Week: {$recentReviews->count()}\n"
            . "Positive (4-5 stars): {$positiveReviews->count()}  |  Critical (1-2 stars): {$negativeReviews->count()}";

        $reviewHighlights = '';
        if ($topReview) {
            $reviewHighlights .= "Top Review ({$topReview->rating}/5): ";
            if ($topReview->text) {
                $reviewHighlights .= '"' . mb_substr($topReview->text, 0, 200) . '"';
            }
            if ($topReview->author_name) {
                $reviewHighlights .= ' — ' . $topReview->author_name;
            }
        }
        if ($criticalReview) {
            if ($reviewHighlights) {
                $reviewHighlights .= "\n\n";
            }
            $reviewHighlights .= "Review Needing Attention ({$criticalReview->rating}/5): ";
            if ($criticalReview->text) {
                $reviewHighlights .= '"' . mb_substr($criticalReview->text, 0, 200) . '"';
            }
        }
        if (! $topReview && ! $criticalReview) {
            $reviewHighlights = 'No new reviews this week.';
        }

        $competitorWatch = '';
        foreach ($business->competitors as $competitor) {
            if ($competitorWatch) {
                $competitorWatch .= "\n";
            }
            $competitorWatch .= $competitor->name . ': ';
            $competitorWatch .= $competitor->google_rating ? $competitor->google_rating . ' stars' : 'No rating data';
            if ($competitor->google_review_count) {
                $competitorWatch .= ' (' . number_format($competitor->google_review_count) . ' total reviews)';
            }
        }
        if (! $competitorWatch) {
            $competitorWatch = 'No competitor data available.';
        }

        $sentimentTrends = "This week you received {$recentReviews->count()} new reviews. "
            . "{$positiveReviews->count()} were positive (4+ stars) and {$negativeReviews->count()} were critical (2 stars or fewer).";

        $actionItems = "• Respond to any unanswered reviews from this week to boost your engagement score.\n"
            . "• Share a positive review on your social media to attract new clients.\n"
            . "• Follow up with clients who left critical reviews to understand their concerns.\n"
            . "• Check competitor pricing or services mentioned in recent reviews.";

        $weekAhead = 'Keep delivering exceptional experiences. Every 5-star review strengthens your competitive position — your clients are your best marketing team. Focus on requesting reviews from satisfied clients this week.';

        $sections = [
            'performance_snapshot' => $performanceSnapshot,
            'review_highlights' => $reviewHighlights,
            'competitor_watch' => $competitorWatch,
            'sentiment_trends' => $sentimentTrends,
            'action_items' => $actionItems,
            'week_ahead' => $weekAhead,
        ];

        $htmlContent = $this->renderHtmlContent($sections);

        return new DigestResult(
            subjectLine: $subjectLine,
            content: $htmlContent,
            contentJson: $sections,
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
