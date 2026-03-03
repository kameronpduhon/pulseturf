# Phase 2: Scraping Service Design

## Overview

Phase 2 builds the scraping infrastructure that fetches Google Business Profile data and reviews via the Outscraper API. It writes to the database schema established in Phase 1 (businesses, competitors, reviews, scrape_logs).

## Architecture

**Approach:** Single service class + two explicit job classes (one for Business, one for Competitor).

**Key decisions:**
- **HTTP client:** Laravel `Http` facade — zero dependencies, easy to mock with `Http::fake()`
- **Retry strategy:** Job-level via Laravel queue (`$tries`, `$backoff`) — service just throws on failure
- **Combined scrape:** Each job fetches both profile data and reviews in one pass
- **Testing:** All mock-based via `Http::fake()` with JSON fixture files

## Components

### 1. OutscraperService (`app/Services/OutscraperService.php`)

Thin HTTP wrapper — API calls in, clean arrays out. No database writes.

**Public methods:**

| Method | Purpose | Returns |
|--------|---------|---------|
| `searchBusiness(string $name, string $address)` | Resolve name+address to Place ID + profile | `array` with place_id, name, rating, review_count, etc. |
| `getReviews(string $placeId, int $limit = 20)` | Fetch latest reviews for a place | `array[]` of review arrays |
| `getBusinessInfo(string $placeId)` | Fetch fresh profile data | `array` with rating, review_count, phone, website, categories, hours |

**Internals:**
- Private `request(string $endpoint, array $params): array` handles auth header, base URL, 30s timeout, response parsing
- Reads API key from `config('services.outscraper.key')`
- Throws `OutscraperException` on non-2xx, empty results, or missing API key
- Maps Outscraper response fields to our expected format

### 2. ScrapeBusinessJob (`app/Jobs/ScrapeBusinessJob.php`)

**Accepts:** `Business $business`

**Flow:**
1. `getBusinessInfo($business->google_place_id)` — fetch fresh profile
2. `getReviews($business->google_place_id, 20)` — fetch latest reviews
3. Update business record: `google_rating`, `google_review_count`, `phone`, `website`, `google_categories`, `google_hours`, `last_scraped_at`
4. Upsert reviews via `updateOrCreate` keyed on `google_review_id` (deduplication)
5. Count new vs. existing reviews
6. Create `ScrapeLog` with status `success` and metrics
7. If first successful scrape, flip `business.status` from `pending_setup` to `active`

**Retry config:**
- `$tries = 3`
- `$backoff = [60, 300, 1800]` (1min, 5min, 30min)
- `$timeout = 120`

**`failed()` method:** Creates `ScrapeLog` with status `failed` and `error_message`.

### 3. ScrapeCompetitorJob (`app/Jobs/ScrapeCompetitorJob.php`)

Identical pattern to `ScrapeBusinessJob` but operates on `Competitor` model. Same retry config, scrape log creation, and review upsert logic.

### 4. OutscraperException (`app/Exceptions/OutscraperException.php`)

Custom exception carrying HTTP status code and response body.

**Static factories:**
- `::apiError(int $status, string $body)` — API returned non-2xx
- `::noResults(string $query)` — search returned empty
- `::missingApiKey()` — no API key configured

### 5. Queue

Both jobs dispatch to the `default` queue. No separate queue name needed at MVP scale.

## Error Handling

| Scenario | Where | Action |
|----------|-------|--------|
| API 5xx | Service throws → Job retries via queue | 3 retries with exponential backoff; `failed()` creates error scrape log |
| API 4xx | Service throws → Job fails immediately | Job checks status code, calls `$this->fail()` to stop retries |
| No results | Service throws `noResults()` | Job catches specifically, creates scrape log with status `no_results` |
| Missing API key | Service throws `missingApiKey()` | Fails fast before hitting API |
| Network timeout | Http timeout → exception → Job retries | Standard retry flow |

Every failure path creates a `ScrapeLog` record for a complete audit trail.

## Testing

All tests use `Http::fake()` — no real API calls.

### Mock Fixtures (`tests/Fixtures/outscraper/`)

JSON files with realistic Outscraper responses:
- `search_business_success.json`
- `get_reviews_success.json`
- `get_business_info_success.json`
- `error_500.json`
- `no_results.json`

### Service Tests (`tests/Feature/Services/OutscraperServiceTest.php`)

- Search returns place_id and profile
- Search throws on no results
- Get reviews returns mapped review arrays
- Get business info returns profile data
- Throws on API error (5xx)
- Throws on missing API key

### Job Tests (`tests/Feature/Jobs/ScrapeBusinessJobTest.php`, `ScrapeCompetitorJobTest.php`)

- Updates business/competitor profile from scrape data
- Upserts reviews without duplicates (existing `google_review_id`)
- Creates success scrape log with correct metrics
- Creates failed scrape log on exception
- Activates `pending_setup` business on first successful scrape
- Retries on server error (5xx)
- Fails immediately on client error (4xx)

## File Summary

| File | Type | Purpose |
|------|------|---------|
| `app/Services/OutscraperService.php` | Service | Outscraper API HTTP wrapper |
| `app/Jobs/ScrapeBusinessJob.php` | Job | Scrape + persist business data |
| `app/Jobs/ScrapeCompetitorJob.php` | Job | Scrape + persist competitor data |
| `app/Exceptions/OutscraperException.php` | Exception | Custom exception for API errors |
| `tests/Feature/Services/OutscraperServiceTest.php` | Test | Service unit tests |
| `tests/Feature/Jobs/ScrapeBusinessJobTest.php` | Test | Business job tests |
| `tests/Feature/Jobs/ScrapeCompetitorJobTest.php` | Test | Competitor job tests |
| `tests/Fixtures/outscraper/*.json` | Fixture | Mock API response data |
