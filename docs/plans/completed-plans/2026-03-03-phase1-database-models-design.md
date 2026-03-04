# Phase 1: Database & Models — Design Document

**Date:** 2026-03-03
**Status:** Approved
**PRD Reference:** pulseturf-PRD.md, Phase 1 (Days 2-3)

---

## Overview

Phase 1 creates the data layer for PulseTurf: 6 new database tables (plus Cashier's built-in tables), 6 Eloquent models with relationships, model factories, and a database seeder. This phase implements the PRD schema with two additions: a `status` column on businesses and a `HasGoogleBusinessData` shared trait.

## Decisions

- **Migration strategy:** One migration per table (standard Laravel convention)
- **Polymorphic relationships:** Reviews and scrape_logs use morphTo (PRD approach)
- **Test infrastructure:** Full factories + seeders included
- **Tweaks from PRD:**
  - Added `status` column to `businesses` table (`pending_setup`, `active`, `suspended`)
  - Added `HasGoogleBusinessData` trait shared between Business and Competitor models

---

## Migrations

### 1. `add_pulseturf_columns_to_users_table`

Adds to existing `users` table:

| Column | Type | Notes |
|---|---|---|
| timezone | varchar(50) | Default: `America/Chicago` |
| trial_ends_at | timestamp | Nullable |

### 2. Cashier Migrations (vendor publish)

Run `php artisan vendor:publish --tag=cashier-migrations`. Creates:
- `subscriptions` table
- `subscription_items` table
- Adds `stripe_id`, `pm_type`, `pm_last_four` columns to `users`

### 3. `create_businesses_table`

| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | Auto-increment |
| user_id | bigint (FK) | cascadeOnDelete |
| name | varchar(255) | Business name as entered |
| google_place_id | varchar(255) | Nullable, resolved after first scrape |
| address | varchar(500) | Full address |
| city | varchar(100) | |
| state | varchar(50) | |
| zip | varchar(10) | |
| phone | varchar(20) | Nullable, from scrape |
| website | varchar(500) | Nullable, from scrape |
| google_rating | decimal(2,1) | Nullable |
| google_review_count | int | Nullable |
| google_categories | json | Nullable |
| google_hours | json | Nullable |
| status | varchar(20) | Default: `pending_setup` |
| last_scraped_at | timestamp | Nullable |
| timestamps | | created_at, updated_at |

### 4. `create_competitors_table`

| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | Auto-increment |
| business_id | bigint (FK) | cascadeOnDelete |
| name | varchar(255) | Competitor name as entered |
| google_place_id | varchar(255) | Nullable |
| address | varchar(500) | |
| city | varchar(100) | |
| state | varchar(50) | |
| zip | varchar(10) | |
| phone | varchar(20) | Nullable |
| website | varchar(500) | Nullable |
| google_rating | decimal(2,1) | Nullable |
| google_review_count | int | Nullable |
| google_categories | json | Nullable |
| google_hours | json | Nullable |
| last_scraped_at | timestamp | Nullable |
| timestamps | | created_at, updated_at |

### 5. `create_reviews_table`

| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | Auto-increment |
| reviewable_type | varchar(255) | Polymorphic type |
| reviewable_id | bigint | Polymorphic FK |
| google_review_id | varchar(255) | Unique index |
| author_name | varchar(255) | |
| author_image | varchar(500) | Nullable |
| rating | tinyint | 1-5 |
| text | text | Nullable |
| published_at | timestamp | When review was posted |
| owner_response | text | Nullable |
| owner_response_at | timestamp | Nullable |
| sentiment | varchar(20) | Nullable (positive/negative/neutral) |
| sentiment_topics | json | Nullable |
| timestamps | | created_at, updated_at |

**Indexes:**
- Unique on `google_review_id`
- Composite on `[reviewable_type, reviewable_id, published_at]`

### 6. `create_scrape_logs_table`

| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | Auto-increment |
| scrapeable_type | varchar(255) | Polymorphic type |
| scrapeable_id | bigint | Polymorphic FK |
| status | varchar(20) | pending/success/failed |
| source | varchar(50) | Default: `outscraper` |
| api_response_code | int | Nullable |
| error_message | text | Nullable |
| reviews_found | int | Nullable |
| new_reviews | int | Nullable |
| rating_at_scrape | decimal(2,1) | Nullable |
| review_count_at_scrape | int | Nullable |
| cost_cents | int | Nullable |
| duration_ms | int | Nullable |
| timestamps | | created_at, updated_at |

**Indexes:**
- Composite on `[scrapeable_type, scrapeable_id, created_at]`

### 7. `create_digests_table`

| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | Auto-increment |
| user_id | bigint (FK) | cascadeOnDelete |
| business_id | bigint (FK) | cascadeOnDelete |
| week_start | date | Monday of digest week |
| subject_line | varchar(255) | Generated subject |
| html_content | longtext | Full rendered HTML |
| plain_content | longtext | Nullable |
| llm_prompt | text | Nullable, for debugging |
| llm_response | text | Nullable, raw LLM output |
| llm_model | varchar(50) | e.g., gpt-4o-mini |
| llm_tokens_used | int | Nullable |
| llm_cost_cents | int | Nullable |
| status | varchar(20) | draft/sent/failed/bounced |
| sent_at | timestamp | Nullable |
| opened_at | timestamp | Nullable |
| clicked_at | timestamp | Nullable |
| timestamps | | created_at, updated_at |

**Indexes:**
- Composite on `[user_id, week_start]`
- Unique on `[business_id, week_start]`

---

## Eloquent Models

### User (modify existing)

```
Traits: HasFactory, Notifiable, Billable
Implements: MustVerifyEmail

Fillable: name, email, password, timezone, trial_ends_at
Casts: email_verified_at → datetime, trial_ends_at → datetime, password → hashed

Relationships:
  hasOne(Business)
  hasMany(Digest)

Helpers:
  isOnTrial(): bool
  hasActiveBusiness(): bool
  competitorLimit(): int — returns 1 or 3 based on subscription
```

### Business

```
Traits: HasFactory, HasGoogleBusinessData

Fillable: user_id, name, google_place_id, address, city, state, zip, phone,
          website, google_rating, google_review_count, google_categories,
          google_hours, status, last_scraped_at

Relationships:
  belongsTo(User)
  hasMany(Competitor)
  morphMany(Review, 'reviewable')
  morphMany(ScrapeLog, 'scrapeable')
  hasMany(Digest)

Scopes: active(), pendingSetup()
```

### Competitor

```
Traits: HasFactory, HasGoogleBusinessData

Fillable: business_id, name, google_place_id, address, city, state, zip, phone,
          website, google_rating, google_review_count, google_categories,
          google_hours, last_scraped_at

Relationships:
  belongsTo(Business)
  morphMany(Review, 'reviewable')
  morphMany(ScrapeLog, 'scrapeable')
```

### HasGoogleBusinessData (Trait)

Shared between Business and Competitor. Provides:
- Casts: google_categories → array, google_hours → array, google_rating → decimal:1, last_scraped_at → datetime
- These casts are merged with any model-specific casts

### Review

```
Traits: HasFactory

Fillable: reviewable_type, reviewable_id, google_review_id, author_name,
          author_image, rating, text, published_at, owner_response,
          owner_response_at, sentiment, sentiment_topics

Casts: sentiment_topics → array, published_at → datetime,
       owner_response_at → datetime, rating → integer

Relationships:
  morphTo(reviewable)

Scopes: recent(int $days = 7), negative(), positive()
```

### ScrapeLog

```
Traits: HasFactory

Fillable: scrapeable_type, scrapeable_id, status, source, api_response_code,
          error_message, reviews_found, new_reviews, rating_at_scrape,
          review_count_at_scrape, cost_cents, duration_ms

Casts: rating_at_scrape → decimal:1

Relationships:
  morphTo(scrapeable)

Scopes: successful(), failed()
```

### Digest

```
Traits: HasFactory

Fillable: user_id, business_id, week_start, subject_line, html_content,
          plain_content, llm_prompt, llm_response, llm_model, llm_tokens_used,
          llm_cost_cents, status, sent_at, opened_at, clicked_at

Casts: week_start → date, sent_at → datetime, opened_at → datetime,
       clicked_at → datetime

Relationships:
  belongsTo(User)
  belongsTo(Business)

Scopes: sent(), draft()
```

---

## Factories

### UserFactory (extend existing)

- Adds `timezone` (random from US timezones) and `trial_ends_at` (now + 14 days)
- States: `expired()` (trial_ends_at in past), `subscribed()` (for Cashier testing)

### BusinessFactory

- Generates realistic med spa names from a pool (e.g., "Glow Aesthetics", "Radiance Med Spa", "Luxe Skin Clinic")
- Random US addresses, ratings 3.5-5.0, review counts 10-300
- States: `pendingSetup()` (status = pending_setup), `active()` (status = active, has google_place_id)

### CompetitorFactory

- Same realistic data pattern as BusinessFactory
- Auto-associates with a Business via `for(Business)`

### ReviewFactory

- Generates realistic med spa review text (pool of templates)
- Ratings weighted toward 4-5 stars
- Random author names, published dates within last 90 days
- States: `negative()` (1-2 stars), `positive()` (4-5 stars), `withOwnerResponse()`

### ScrapeLogFactory

- Generates logs with realistic durations (500-5000ms) and costs (1-10 cents)
- States: `successful()` (status = success), `failed()` (status = failed, with error_message)

### DigestFactory

- Generates digest records with placeholder HTML content
- States: `sent()` (status = sent, sent_at filled), `draft()` (status = draft)

---

## DatabaseSeeder

Creates a complete demo scenario:

- 1 User ("Demo Owner", trial active, timezone America/Chicago)
- 1 Business ("Glow Aesthetics", status active, rating 4.8, 142 reviews)
- 3 Competitors (varied ratings 4.3-4.7, varied review counts)
- ~20 reviews per entity (mix of positive/negative, with sentiment)
- 1 successful scrape log per entity
- 2 past digests (sent) + 1 current week (draft)
