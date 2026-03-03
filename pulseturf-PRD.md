# PulseTurf — Product Requirements Document

**Version:** 2.0 (Build-Ready)
**Last Updated:** 2026-03-03
**Launch Niche:** Med Spas & Aesthetic Clinics
**Stack:** Laravel + Livewire
**Status:** Ready for development

---

## The Pitch

Every med spa owner is running blind. They don't know that their biggest competitor just got three 1-star reviews about botched lip filler. They don't know that a new aesthetic clinic opened two miles away last month. They don't know that their own Google reviews shifted negative this week because two clients complained about wait times.

They're too busy injecting Botox and running a business to monitor the competitive landscape. And the tools that could help them — Birdeye, Podium, RepuGen — cost $300–$500/month, require active management, and are designed for enterprises, not a solo med spa owner with 15 reviews.

**PulseTurf fixes this with zero effort.** You enter your med spa, your competitors, and your zip code. Every Monday morning at 7 AM, an email lands in your inbox: competitor review trends, sentiment shifts in your own reviews, and a plain-English briefing of what you should pay attention to this week. No dashboard. No login. No app. Just a weekly intelligence briefing that makes you feel like you have a marketing analyst on staff — for $29/month.

This is a pure automation play. The AI scrapes, summarizes, and writes. You collect the subscription. At 100 customers averaging $54/mo (mix of Starter and Pro), that's **$5,400 MRR** for a system that costs ~$200/month to run.

---

## Problem

Med spa owners face a unique combination of challenges:

- **Review sensitivity is extreme**: One bad review about a cosmetic procedure can tank bookings. Med spa clients research heavily before committing to treatments — Google reviews are the #1 decision factor.
- **Competitive density**: Med spas cluster in affluent suburbs. A typical market has 5–15 competitors within a 10-mile radius, and new ones open constantly.
- **Review blindness**: They check their own Google Reviews sporadically. They NEVER systematically track competitor reviews or spot trends.
- **Competitor moves go unnoticed**: A competitor adds hydrafacial, drops Botox pricing, gets a wave of negative reviews (opportunity!) — the owner finds out months later, if ever.
- **Tool overload & cost**: Existing reputation tools cost $300–$500/mo and focus on *generating* reviews, not competitive intelligence. Most med spas pay for nothing or overpay for bloated platforms.

**The gap**: A dead-simple, affordable, automated competitive intelligence product specifically for local service businesses — starting with med spas.

---

## Solution

PulseTurf is an automated weekly email briefing for med spa owners.

The system:
1. Takes in a user's business name, location, and competitors (1 or 3 depending on tier)
2. Runs automated weekly data pulls from Google Business Profile via API
3. Feeds raw data into an LLM that synthesizes trends and flags actionable insights
4. Generates a clean, plain-English email digest
5. Sends it every Monday at 7 AM local time

**That's the entire product for MVP.** No app. No dashboard. Email is the interface.

---

## Target Users (Launch Niche)

**Primary**: Independent med spa / aesthetic clinic owners with 1–3 locations.

**Psychographic profile**: 
- 30–55 years old
- Often a nurse practitioner, PA, or physician who owns the practice
- Understands the importance of reviews but doesn't have time to monitor them
- Currently uses no tools, or pays for an expensive platform they barely use
- Checks email religiously (especially Monday morning before the week starts)
- Will pay $29–$79/month without blinking if it saves them time and delivers value
- NOT tech-savvy — needs zero-friction onboarding

**Why med spas first**:
- High review sensitivity (cosmetic procedures = high-stakes decisions for clients)
- Clustered competition (easy to identify competitors)
- Strong willingness to pay for marketing/growth tools
- Underserved by current solutions at this price point
- Easy to find and contact (Google Maps lists them all)

---

## Competitive Positioning — Med Spa Landscape

### What med spa owners currently use:

| Tool | Price | What It Does | Gap |
|---|---|---|---|
| **Podium** | $399+/mo | Review generation, messaging, payments | No competitive intel. Expensive. Requires active use. |
| **Birdeye** | $300+/mo | Review management, listings, surveys | Enterprise-focused. No competitor tracking. |
| **RepuGen** | $199+/mo | Patient satisfaction surveys, review requests | Healthcare-specific but focused on generating reviews, not competitive analysis. |
| **AestheticsPro** | $150+/mo | EMR + review requests (bundled) | Med spa EMR that adds reviews as a feature. Not intelligence. |
| **GoHighLevel** | $97–$297/mo | CRM, review automation, funnels | Swiss army knife — powerful but complex. Requires setup. |
| **Freshreview** | $29+/mo | Basic review monitoring | Closest competitor in price, but no competitor tracking or AI synthesis. |
| **Google Alerts** | Free | Keyword monitoring | Primitive. No business context. No synthesis. |
| **Manual checking** | Free | Owner checks Google occasionally | This is what 80% of med spas actually do. |

### PulseTurf positioning:

**"The competitive intelligence briefing that does the work for you."**

Every existing tool focuses on *generating* reviews (asking patients to leave them). **None of them** provide a weekly competitive intelligence briefing that tells you what's happening across your market. PulseTurf doesn't compete with review generation tools — it complements them. A med spa owner can use Podium to generate reviews AND PulseTurf to understand what's happening in their competitive landscape.

**Key differentiator**: Zero effort. No login. No dashboard. No configuration after setup. Just a Monday morning email that makes you smarter about your market.

---

## Revenue Model

| Tier | Price (Monthly) | Price (Annual) | Features |
|---|---|---|---|
| **Starter** | $29/mo | $290/yr ($24.17/mo) | 1 business + 1 competitor, weekly email digest |
| **Pro** | $79/mo | $790/yr ($65.83/mo) | 1 business + 3 competitors, weekly email digest |

- **Free trial**: 14 days, no credit card required
- **Annual discount**: 2 months free (effectively ~17% off)
- **Agency tier**: Post-MVP consideration
- **Target blended ARPU**: ~$54/mo (assuming 60/40 Starter/Pro split)

At 100 customers (60 Starter + 40 Pro):
- Monthly: (60 × $29) + (40 × $79) = **$4,900 MRR** = **$58,800 ARR**

---

## Scraping API Selection

### Comparison

| Factor | Outscraper | SerpAPI | DataForSEO |
|---|---|---|---|
| **Pricing model** | Pay-as-you-go | Monthly subscription tiers | Pay-as-you-go |
| **Business profile cost** | $0.003/record | ~$0.015–$0.025/search | $0.0015/profile |
| **Review cost** | $0.003/review | Included in search (10 per page) | $0.00075/10 reviews (standard) |
| **Rate limits** | Generous | Tied to plan tier | Generous |
| **Data quality** | Excellent (full GBP data) | Excellent (SERP-based) | Excellent (full GBP data + reviews) |
| **Business info included** | ✅ Hours, photos, attributes | ✅ Via Maps API | ✅ Full GMB profile |
| **Review text + rating** | ✅ | ✅ | ✅ |
| **Review date** | ✅ | ✅ | ✅ |
| **Owner responses** | ✅ | ✅ | ✅ |
| **Turnaround time** | Seconds–minutes | Seconds | Up to 45 min (standard) or 1 min (priority) |
| **Laravel SDK/HTTP** | REST API | REST API + PHP client | REST API |
| **Minimum monthly cost** | $0 (pay per use) | $25/mo (Starter: 1,000 searches) | $0 (pay per use) |

### Cost-Per-Customer Math

**Weekly scrape per profile**: 1 business info pull + 1 reviews pull (latest 20 reviews)

**Starter customer** (2 profiles/week): 4 weeks × 2 profiles = 8 business pulls + 8 review pulls/mo
**Pro customer** (4 profiles/week): 4 weeks × 4 profiles = 16 business pulls + 16 review pulls/mo

| API | Starter Cost/Mo | Pro Cost/Mo | Notes |
|---|---|---|---|
| **Outscraper** | ~$0.27 | ~$0.53 | $0.003/record + $0.003/review × ~20 reviews/pull |
| **SerpAPI** | ~$0.24 | ~$0.48 | $0.015/search at Developer tier ($75/mo minimum) |
| **DataForSEO** | ~$0.04 | ~$0.07 | $0.0015/profile + $0.0015/10 reviews (priority) |

### Recommendation: **Outscraper**

Despite DataForSEO being cheapest per-query, **Outscraper is the best choice for MVP** because:

1. **Simplicity**: Dead-simple REST API. One call gets full business data. One call gets all reviews. No queue management (DataForSEO requires POST task → poll for results).
2. **Speed**: Results return in seconds, not 45 minutes. Critical for onboarding (first scrape should complete while user is still on the welcome page).
3. **Free tier**: First 500 businesses + 500 reviews free — enough to build and test without spending a dime.
4. **Data quality**: Returns everything we need in one call — rating, review count, hours, photos, categories, individual reviews with text/rating/date/owner_response.
5. **Cost is still tiny**: At $0.27/Starter customer and $0.53/Pro customer, the cost difference vs DataForSEO is negligible against our revenue.
6. **No minimum spend**: Pay-as-you-go means $0/mo at low volume. SerpAPI requires $25–$75/mo minimum regardless of usage.

**At 100 customers** (60 Starter + 40 Pro): (60 × $0.27) + (40 × $0.53) = **~$37/mo in scraping costs**.

We can always migrate to DataForSEO later for cost optimization at scale (1000+ customers).

---

## Unit Economics

### Cost per customer per month

| Cost Component | Starter | Pro | Notes |
|---|---|---|---|
| **Outscraper (scraping)** | $0.27 | $0.53 | 8 or 16 pulls/mo |
| **OpenAI GPT-4o-mini (LLM)** | $0.03 | $0.06 | ~1,500 input tokens + 800 output tokens per digest × 4/mo |
| **Email sending (Resend)** | $0.00 | $0.00 | Free tier covers 3,000 emails/mo; then $0.00065/email |
| **Stripe fees** | $1.17 | $2.59 | 2.9% + $0.30 per charge |
| **Hosting (shared)** | $0.10 | $0.10 | Estimated share of ~$10/mo server |
| **Total COGS** | **$1.57** | **$3.28** | |
| **Revenue** | $29.00 | $79.00 | |
| **Gross Profit** | **$27.43** | **$75.72** | |
| **Gross Margin** | **94.6%** | **95.8%** | |

### At 100 customers (60 Starter / 40 Pro)

| Metric | Amount |
|---|---|
| Monthly Revenue | $4,900 |
| Monthly COGS | ~$225 |
| Gross Profit | ~$4,675 |
| Gross Margin | ~95.4% |

**These are SaaS dream margins.** The product is almost pure profit after fixed costs (domain, hosting, your time).

### Fixed monthly costs (regardless of customer count)

| Item | Cost |
|---|---|
| Server (Forge + DigitalOcean or similar) | $12–$20/mo |
| Domain | ~$1/mo (amortized) |
| Resend (email) | $0 until 3,000 emails/mo, then $20/mo |
| Outscraper | Pay per use only |
| Stripe | Per-transaction only |
| **Total fixed** | **~$15–$25/mo** |

---

## Data Model / Schema

### Entity Relationship Overview

```
users
  ├── subscriptions (1:1)
  ├── businesses (1:1 per user for MVP)
  │     ├── competitors (1:many, 1 or 3)
  │     ├── scrape_logs (1:many)
  │     └── reviews (1:many)
  └── digests (1:many)

competitors
  ├── scrape_logs (1:many)
  └── reviews (1:many)
```

### Tables

#### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| name | varchar(255) | |
| email | varchar(255) | unique |
| email_verified_at | timestamp | nullable |
| password | varchar(255) | |
| timezone | varchar(50) | e.g., "America/Chicago" — for 7 AM local delivery |
| trial_ends_at | timestamp | nullable, set to now + 14 days on signup |
| stripe_id | varchar(255) | nullable, Cashier column |
| pm_type | varchar(25) | nullable, Cashier column |
| pm_last_four | varchar(4) | nullable, Cashier column |
| remember_token | varchar(100) | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `businesses`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| user_id | bigint (FK → users) | |
| name | varchar(255) | Business name as entered |
| google_place_id | varchar(255) | nullable, resolved after first scrape |
| address | varchar(500) | Full address |
| city | varchar(100) | |
| state | varchar(50) | |
| zip | varchar(10) | |
| phone | varchar(20) | nullable, from scrape |
| website | varchar(500) | nullable, from scrape |
| google_rating | decimal(2,1) | nullable, e.g., 4.7 |
| google_review_count | int | nullable |
| google_categories | json | nullable, array of category strings |
| google_hours | json | nullable, structured hours object |
| last_scraped_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `competitors`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| business_id | bigint (FK → businesses) | |
| name | varchar(255) | Competitor name as entered |
| google_place_id | varchar(255) | nullable |
| address | varchar(500) | |
| city | varchar(100) | |
| state | varchar(50) | |
| zip | varchar(10) | |
| phone | varchar(20) | nullable |
| website | varchar(500) | nullable |
| google_rating | decimal(2,1) | nullable |
| google_review_count | int | nullable |
| google_categories | json | nullable |
| google_hours | json | nullable |
| last_scraped_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `reviews`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| reviewable_type | varchar(255) | "App\Models\Business" or "App\Models\Competitor" (polymorphic) |
| reviewable_id | bigint | FK to businesses or competitors |
| google_review_id | varchar(255) | unique, from Google |
| author_name | varchar(255) | |
| author_image | varchar(500) | nullable |
| rating | tinyint | 1–5 |
| text | text | nullable (some reviews have no text) |
| published_at | timestamp | When the review was posted |
| owner_response | text | nullable |
| owner_response_at | timestamp | nullable |
| sentiment | varchar(20) | nullable, "positive"/"negative"/"neutral" — set by LLM |
| sentiment_topics | json | nullable, e.g., ["wait time", "staff friendliness"] |
| created_at | timestamp | When we first scraped it |
| updated_at | timestamp | |

**Index**: `unique(google_review_id)` to prevent duplicates on re-scrape.

#### `scrape_logs`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| scrapeable_type | varchar(255) | Polymorphic |
| scrapeable_id | bigint | FK to businesses or competitors |
| status | varchar(20) | "pending", "success", "failed" |
| source | varchar(50) | "outscraper" (for future multi-source) |
| api_response_code | int | nullable |
| error_message | text | nullable |
| reviews_found | int | nullable |
| new_reviews | int | nullable, reviews we hadn't seen before |
| rating_at_scrape | decimal(2,1) | nullable, snapshot of rating |
| review_count_at_scrape | int | nullable, snapshot of total reviews |
| cost_cents | int | nullable, track API cost per scrape |
| duration_ms | int | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `digests`
| Column | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| user_id | bigint (FK → users) | |
| business_id | bigint (FK → businesses) | |
| week_start | date | Monday of the digest week |
| subject_line | varchar(255) | Generated subject line |
| html_content | longtext | Full rendered HTML email |
| plain_content | longtext | nullable, plain text version |
| llm_prompt | text | nullable, store for debugging/iteration |
| llm_response | text | nullable, raw LLM output before rendering |
| llm_model | varchar(50) | e.g., "gpt-4o-mini" |
| llm_tokens_used | int | nullable |
| llm_cost_cents | int | nullable |
| status | varchar(20) | "draft", "sent", "failed", "bounced" |
| sent_at | timestamp | nullable |
| opened_at | timestamp | nullable (if tracking opens) |
| clicked_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `subscriptions` (Laravel Cashier)
Handled by Laravel Cashier's built-in migration. Columns include:
- user_id, stripe_id, stripe_status, stripe_price, quantity, trial_ends_at, ends_at, created_at, updated_at

#### `subscription_items` (Laravel Cashier)
Also handled by Cashier's migration.

#### `plans` (reference/config — can be seeded or config file)
| Plan | Stripe Price ID | Monthly | Annual | Business Limit | Competitor Limit |
|---|---|---|---|---|---|
| starter_monthly | price_xxx | $29 | — | 1 | 1 |
| starter_annual | price_xxx | — | $290 | 1 | 1 |
| pro_monthly | price_xxx | $79 | — | 1 | 3 |
| pro_annual | price_xxx | — | $790 | 1 | 3 |

---

## Email Digest Spec

### Design Principles
- Scannable in **60 seconds** — a med spa owner reading this with coffee before their first appointment
- **Mobile-first** — 70%+ will read on phone
- **Plain English** — no jargon, no charts (save for dashboard post-MVP)
- **Actionable** — every section ends with a "so what" or suggestion
- Tone: **Smart friend who happens to be a marketing analyst** — warm, direct, occasionally witty
- Length: **~500–800 words** (equivalent to a 2–3 minute read)

### Email Structure

```
Subject: "Your Weekly Pulse: [Business Name] — Week of [Date]"
Preview text: "[One-line hook, e.g., 'Your rating held steady. Competitor X dropped 0.2 stars.']"

FROM: PulseTurf <digest@pulseturf.com>
```

**Section Order:**

#### 1. 🏥 Your Snapshot (Always first — the owner cares about themselves most)
- Current Google rating + change from last week (↑ ↓ →)
- Total review count + new reviews this week
- One-sentence sentiment summary of new reviews

#### 2. ⭐ Review Highlights (Only if new reviews exist)
- Top positive review snippet (1–2 sentences) with star rating
- Any negative review flagged with suggested response angle
- Common themes mentioned (e.g., "Two reviews mentioned your relaxing atmosphere")

#### 3. 👀 Competitor Watch (The "intel" — this is the differentiator)
- For each tracked competitor:
  - Name, rating, change from last week
  - New review count
  - Notable review snippets (especially negative ones = your opportunity)
  - Any changes detected (hours, new photos, etc.)

#### 4. 💡 This Week's Insight (AI-generated actionable takeaway)
- One specific, actionable recommendation
- Examples: "Competitor X got 2 complaints about pricing transparency this week. Consider highlighting your transparent pricing in your next social post."
- This is where the LLM earns its keep

#### 5. 📊 Quick Numbers (At-a-glance comparison table)
- Simple text-based comparison:
  ```
  You: ⭐ 4.8 (142 reviews) → 
  Competitor A: ⭐ 4.5 (89 reviews) ↓
  Competitor B: ⭐ 4.6 (203 reviews) ↑
  ```

#### 6. Footer
- "Was this useful?" → 👍 / 👎 (one-click links)
- Manage preferences | Unsubscribe
- PulseTurf branding (minimal)

### Example Digest

```
Subject: Your Weekly Pulse: Glow Aesthetics — Week of March 3
Preview: Your rating held steady at 4.8 ⭐. Radiance Med Spa dropped to 4.3.

─────────────────────────────────

🏥 YOUR SNAPSHOT

Glow Aesthetics — 4.8 ⭐ (142 reviews) →
You received 3 new reviews this week, all positive. Clients 
loved your "welcoming staff" and "clean, modern space."
Your rating held steady. Nice work.

─────────────────────────────────

⭐ REVIEW HIGHLIGHTS

★★★★★ "Best Botox experience I've ever had. Sarah was 
so gentle and the results are exactly what I wanted. Will 
be back for lip filler next month!" — Jessica M.

★★★★☆ "Great results but had to wait 25 minutes past my 
appointment time." — Amanda K.
  💡 Consider responding to Amanda's review acknowledging 
  the wait and mentioning any scheduling improvements 
  you've made.

Common themes this week: staff friendliness (2 mentions), 
results quality (2 mentions), wait time (1 mention).

─────────────────────────────────

👀 COMPETITOR WATCH

Radiance Med Spa — 4.3 ⭐ (89 reviews) ↓ dropped 0.2
  2 new reviews this week, both negative.
  ★★☆☆☆ "Overpriced and the receptionist was rude."
  ★★☆☆☆ "My filler looked uneven. Had to go back twice."
  🔥 Opportunity: Radiance is struggling with service 
  quality. Your reviews are trending opposite — consider 
  running a "new client" promotion to capture defectors.

Luxe Skin Clinic — 4.6 ⭐ (203 reviews) →
  1 new review, positive. No significant changes.

Beauty Bar ATX — 4.7 ⭐ (67 reviews) ↑ gained 0.1
  4 new reviews, all 5-star. They're gaining momentum. 
  Worth watching — they added "PRP facial" to their 
  Google Business categories this week.

─────────────────────────────────

💡 THIS WEEK'S INSIGHT

Beauty Bar ATX added PRP facials and is getting strong 
reviews for it. If you offer PRP or similar regenerative 
treatments, make sure they're listed in your Google 
Business categories — clients searching "PRP facial 
near me" won't find you if it's not there.

─────────────────────────────────

📊 QUICK NUMBERS

You (Glow):       ⭐ 4.8  (142 reviews)  →
Radiance:         ⭐ 4.3  (89 reviews)   ↓
Luxe Skin:        ⭐ 4.6  (203 reviews)  →
Beauty Bar ATX:   ⭐ 4.7  (67 reviews)   ↑

─────────────────────────────────

Was this useful?  👍 Yes  |  👎 No

Manage preferences · Unsubscribe
Powered by PulseTurf
```

### LLM Prompt Strategy

The digest is generated by passing structured scrape data to GPT-4o-mini with a system prompt that:
- Sets the tone (smart, friendly marketing analyst)
- Provides the template structure
- Instructs it to be specific and actionable (no generic advice)
- Limits output to ~600 words
- Tells it to highlight opportunities from competitor weaknesses
- Includes the business category (med spa) for industry-relevant suggestions

---

## Onboarding Flow

### Step 1: Landing Page
**URL**: pulseturf.com

- Hero: "Know what your competitors are doing. Every Monday morning."
- Subhead: "Automated Google review intelligence for med spas. No dashboard. No login. Just a weekly email briefing."
- CTA: "Start Free Trial →" (big button)
- Social proof section (post-launch): testimonials, review count
- How it works: 3-step visual (Enter business → We monitor → Get your Monday briefing)
- Pricing section with Starter/Pro comparison
- FAQ

**Tech**: Static Blade template, no auth required to view.

### Step 2: Signup
**URL**: pulseturf.com/register

- Fields: Name, Email, Password, Timezone (auto-detected from browser)
- No credit card required
- Submit → create user with `trial_ends_at = now + 14 days`
- Send email verification link
- Redirect to Step 3

### Step 3: Business Setup
**URL**: pulseturf.com/setup

- Livewire form, 2 parts:

**Part A — Your Business:**
- Business name (text input)
- Business address (text input, or Google Places autocomplete if we add it)
- City, State, Zip
- "Find My Business" button → hits Outscraper to resolve Google Place ID and pull initial data
- Show confirmation card: "Is this your business?" with Google rating, review count, photo
- Confirm → save to `businesses` table

**Part B — Your Competitors:**
- "Who are your top competitors?" 
- Competitor name + address fields (1 field for Starter, 3 for Pro)
- Same resolve + confirm flow for each
- "I'm not sure" option → we can suggest competitors based on Google Maps search for same category + nearby location (nice-to-have)

### Step 4: First Scrape (Background)
- Triggered immediately after business setup
- Queue job: `ScrapeBusinessJob` and `ScrapeCompetitorJob` for each
- Pull full business profile + last 20 reviews for each entity
- Run sentiment analysis on reviews
- Show Livewire loading state: "Setting up your first intelligence briefing..."
- On completion: "✅ We found [X] reviews for your business and [Y] for your competitors. Your first briefing is on its way!"

### Step 5: Welcome Email
- Sent immediately after first scrape completes
- Subject: "Welcome to PulseTurf — your first briefing is almost ready"
- Content:
  - Confirm what we're monitoring (business name + competitors)
  - Quick preview: "Your current rating: 4.8 ⭐ with 142 reviews"
  - Set expectations: "Your first full briefing arrives Monday at 7 AM"
  - Link to upgrade to Pro if on Starter
  - Support email

### Step 6: First Digest (Next Monday)
- Standard weekly digest email
- Includes a "first edition" intro paragraph: "This is your first PulseTurf briefing! Here's what we found..."

### Step 7: Trial Expiration Flow (Day 12, 13, 14)
- **Day 12**: Email — "Your trial ends in 2 days. Add a payment method to keep your Monday briefings coming."
- **Day 13**: Email — "Tomorrow is your last day. Here's what you'd miss..." (preview of what next week's digest would contain)
- **Day 14**: Email — "Your trial has ended. Reactivate anytime to pick up where you left off." 
- After expiration: stop sending digests, keep data for 30 days, then archive

---

## Error Handling & Monitoring

### Scrape Failures

| Scenario | Handling |
|---|---|
| Outscraper API returns error (5xx) | Retry 3x with exponential backoff (1min, 5min, 30min). Log to `scrape_logs` with status "failed". |
| Outscraper API returns empty/no results | Log as "no_results". Use last known data for digest. Add note in digest: "We couldn't pull fresh data for [competitor] this week." |
| Google Place ID not found | During onboarding: show "We couldn't find this business on Google. Please check the name and address." Allow manual entry of Google Maps URL. |
| Rate limit hit | Queue remaining scrapes with delay. Our volume is low enough this shouldn't happen. |
| All scrapes fail for a user | Skip digest for that week. Send email: "We hit a snag this week — your briefing will be back next Monday." |

### Email Failures

| Scenario | Handling |
|---|---|
| Email bounces (hard bounce) | Mark user email as invalid. Send notification to admin. Pause digests. |
| Email bounces (soft bounce) | Retry once. If fails again, log and continue. |
| User marks as spam | Resend handles this via webhook. Auto-unsubscribe user. Log event. |
| Email not opened for 4+ weeks | Send re-engagement email: "Still want your Monday briefings?" If no open after 2 more weeks, pause digests (save scraping costs). |

### Stripe / Payment Failures

| Scenario | Handling |
|---|---|
| Card declined on subscription creation | Show error, ask to try another card. Don't create subscription. |
| Recurring payment fails | Stripe retries automatically (Smart Retries). After final failure, Cashier marks subscription as "past_due". |
| Past due > 7 days | Pause digests. Send email: "Your payment failed. Update your card to keep your briefings." |
| Past due > 14 days | Cancel subscription. Send final email. Keep data for 30 days. |
| Trial expires, no conversion | Stop digests. Send 3-email winback sequence over next 2 weeks. |

### LLM Failures

| Scenario | Handling |
|---|---|
| OpenAI API error | Retry 2x. If still failing, generate a "data-only" digest without AI commentary (just the numbers and review snippets). |
| LLM output is garbage/off-topic | Basic validation: check output length, presence of expected sections. If fails, regenerate with slightly different prompt. |
| LLM cost spike | Set a per-digest token budget cap. Alert admin if monthly LLM spend exceeds $20. |

### Monitoring (Simple for MVP)

- **Laravel Telescope** in production for debugging (or disable and use log files to save resources)
- **Email alerts to admin** (Kameron) for: scrape failures, payment failures, new signups, cancellations
- **Weekly admin summary** (simple Artisan command): total users, MRR, scrape success rate, email open rate
- **Sentry or Flare** for exception tracking (Flare is Laravel-native, free tier available)

---

## MVP Build Plan

### Phase 0: Project Setup (Day 1)
1. Create new Laravel project: `laravel new pulseturf`
2. Initialize git repo, push to GitHub (`kameronpduhon/pulseturf`)
3. Install dependencies: Livewire, Laravel Cashier, Resend mail driver
4. Set up `.env` with Outscraper API key, OpenAI key, Stripe keys, Resend key
5. Configure Forge or similar for deployment (can do later)
6. Set up Stripe account with test mode, create 4 price IDs (starter monthly, starter annual, pro monthly, pro annual)

### Phase 1: Database & Models (Days 2–3)
1. Create migration: `users` table (add timezone, trial_ends_at columns to default)
2. Run Cashier migration for subscriptions
3. Create migration: `businesses` table
4. Create migration: `competitors` table  
5. Create migration: `reviews` table (polymorphic)
6. Create migration: `scrape_logs` table (polymorphic)
7. Create migration: `digests` table
8. Create Eloquent models with relationships:
   - `User` → hasOne Business, hasMany Digests, subscription (Cashier)
   - `Business` → belongsTo User, hasMany Competitors, morphMany Reviews, morphMany ScrapeLogs
   - `Competitor` → belongsTo Business, morphMany Reviews, morphMany ScrapeLogs
   - `Review` → morphTo reviewable
   - `ScrapeLog` → morphTo scrapeable
   - `Digest` → belongsTo User, belongsTo Business
9. Create model factories and seeders for testing

### Phase 2: Scraping Service (Days 4–6)
1. Create `OutscraperService` class (in `app/Services/`)
   - Method: `searchBusiness(name, address)` → returns business data + place_id
   - Method: `getReviews(place_id, limit=20)` → returns recent reviews
   - Method: `getBusinessInfo(place_id)` → returns profile data (rating, hours, etc.)
2. Create `ScrapeBusinessJob` (queued job)
   - Calls OutscraperService
   - Updates `businesses` table with latest data
   - Upserts new reviews into `reviews` table (skip duplicates via google_review_id)
   - Creates `scrape_logs` entry
3. Create `ScrapeCompetitorJob` (same pattern for competitors)
4. Write tests for scraping service (mock API responses)
5. Test end-to-end with a real med spa (use Outscraper free tier)

### Phase 3: Auth & Onboarding (Days 7–9)
1. Set up Laravel Breeze for auth scaffolding (register, login, email verification)
2. Customize registration to add timezone field (auto-detect via JS)
3. Set `trial_ends_at` on user creation
4. Create `/setup` route and Livewire component for business setup
   - Part A: Business name/address → "Find My Business" button → Outscraper search → confirm card
   - Part B: Competitor entry (1 or 3 based on plan)
5. After setup, dispatch `ScrapeBusinessJob` + `ScrapeCompetitorJob` for each entity
6. Create setup completion page with Livewire polling for scrape completion
7. Redirect to simple "You're all set!" page after scrapes complete

### Phase 4: Digest Generation (Days 10–13)
1. Create `DigestGeneratorService` class
   - Gathers data: business profile, competitor profiles, new reviews since last digest, previous digest data for comparison
   - Builds structured prompt for GPT-4o-mini
   - Calls OpenAI API
   - Parses response into digest sections
2. Create `GenerateDigestJob` (queued)
   - Calls DigestGeneratorService
   - Renders HTML email template (Blade)
   - Stores in `digests` table
3. Create Blade email template (`resources/views/emails/digest.blade.php`)
   - Mobile-responsive HTML email
   - Matches the design spec above
   - Includes tracking pixel for opens (optional)
   - Includes feedback links (👍/👎)
4. Create `SendDigestJob` (queued, delayed to 7 AM user local time)
   - Sends the rendered email via Resend
   - Updates digest status to "sent"
5. Create Artisan command: `php artisan digest:generate-weekly`
   - Finds all active users (subscribed or trial)
   - Dispatches ScrapeBusinessJob + ScrapeCompetitorJob for all profiles
   - After scrapes complete, dispatches GenerateDigestJob for each user
   - After generation, dispatches SendDigestJob at 7 AM user local time
6. Schedule command in `routes/console.php`: run every Sunday at midnight (gives time for scrapes + generation before Monday 7 AM delivery)
7. Write tests: mock OpenAI, verify digest output structure

### Phase 5: Stripe Billing (Days 14–16)
1. Configure Laravel Cashier
2. Create billing page (`/billing`) with Livewire component
   - Show current plan (or trial status)
   - Plan selection: Starter vs Pro, Monthly vs Annual
   - Stripe Elements card input
   - Subscribe button
3. Create Stripe webhook handler for:
   - `invoice.payment_succeeded` → activate/continue subscription
   - `invoice.payment_failed` → mark past_due, trigger email
   - `customer.subscription.deleted` → handle cancellation
4. Create trial expiration logic:
   - Scheduled command checks daily for users where `trial_ends_at` is approaching
   - Day 12, 13: send reminder emails
   - Day 14: if no subscription, mark as expired, stop digests
5. Create subscription middleware to gate `/setup` for expired trials
6. Test full flow in Stripe test mode

### Phase 6: Landing Page & Polish (Days 17–19)
1. Design and build landing page (Blade + Tailwind CSS)
   - Hero, how it works, pricing, FAQ
   - Mobile responsive
   - CTA buttons link to /register
2. Create welcome email (sent after first scrape)
3. Create trial reminder emails (day 12, 13, 14)
4. Create payment failure email
5. Add basic "settings" page: update email, timezone, manage subscription, cancel
6. Add unsubscribe link handling in digest emails
7. Test complete flow end-to-end: signup → setup → scrape → digest → billing

### Phase 7: Deploy & Launch Prep (Days 20–21)
1. Set up production server (Forge + DigitalOcean $6/mo droplet)
2. Configure domain: pulseturf.com (or .com if available)
3. SSL, DNS, environment variables
4. Set up queue worker (Supervisor via Forge)
5. Set up cron for Laravel scheduler
6. Switch Stripe to live mode
7. Create 5 test accounts with real med spas in different cities
8. Send test digests, verify everything works
9. Soft launch: manually sign up 5–10 beta users from cold outreach

**Total estimated build time: 3 weeks** (working 4–5 hours/day)

---

## Tech Stack (Confirmed)

| Component | Choice | Notes |
|---|---|---|
| **Framework** | Laravel 11 + Livewire 3 | Same stack as Project Duhon |
| **Database** | MySQL 8 (or PostgreSQL) | Via Forge/DigitalOcean |
| **Scraping API** | Outscraper | REST API, pay-as-you-go |
| **LLM** | OpenAI GPT-4o-mini | ~$0.03/digest |
| **Email** | Resend | Laravel mail driver available, free tier: 3,000/mo |
| **Billing** | Stripe via Laravel Cashier | |
| **Hosting** | DigitalOcean + Laravel Forge | $6 droplet + $12/mo Forge |
| **Queue** | Laravel Queue (database driver) | Redis if needed later |
| **CSS** | Tailwind CSS | Built into Laravel |
| **Cron** | Laravel Scheduler | Via server cron |
| **Error Tracking** | Flare (free tier) | Laravel-native |

---

## Go-To-Market (Med Spa Focused)

### Channel Strategy (No face required)

1. **Cold email med spas directly** 
   - Scrape Google Maps for "med spa" + "aesthetic clinic" in target cities
   - Use Apollo.io or Outscraper (we already have it!) to find owner emails
   - Send personalized cold emails with free trial
   - Target: 50 emails/day, 5 cities/week
   - Expected conversion: 2–5% signup rate

2. **Med spa Facebook groups & forums**
   - Join groups like "Med Spa Business Owners", "Aesthetic Nurse Practitioners"
   - Post value-forward content about competitor monitoring
   - Mention PulseTurf naturally when relevant

3. **Content SEO (Medium-term)**
   - Blog posts: "How to track competitor reviews for your med spa"
   - "Med spa marketing: what your competitors know that you don't"
   - Target long-tail keywords with low competition

4. **Partner with med spa consultants**
   - Many aesthetic industry consultants advise on marketing
   - Offer them a referral commission or white-label option

5. **Reddit**: r/medspa, r/Entrepreneur, r/smallbusiness

### Launch Cities (Start with 3–5)
Target cities with high med spa density:
- Austin, TX
- Scottsdale, AZ
- Miami, FL
- Nashville, TN
- Dallas, TX

---

## Risk & Mitigation

| Risk | Impact | Mitigation |
|---|---|---|
| **Outscraper API goes down or changes pricing** | High | Abstract behind `ScrapingService` interface. Can swap to DataForSEO in a day. |
| **Google changes Business Profile structure** | Medium | Outscraper handles this — they maintain scrapers. That's what we pay them for. |
| **Low cold email conversion** | Medium | Lead with free trial. Test 3 subject line variants. Parallel channels (Facebook groups, Reddit). |
| **Digest feels generic/not valuable** | High | Invest heavily in prompt engineering. Add feedback mechanism (👍/👎). Iterate weekly based on feedback. |
| **Churn after trial** | Medium | Make trial experience excellent. Send best possible first digest. Follow up personally for first 50 users. |
| **HIPAA concerns** | Low | We only scrape public Google data. No patient data ever touches our system. Clarify this in FAQ. |
| **Competitor copies the idea** | Low | Speed is the moat. First to market in the niche wins. By the time someone copies, we have 100 customers and real feedback loops. |

---

## Success Metrics

| Metric | Target (Month 1) | Target (Month 3) | Target (Month 6) |
|---|---|---|---|
| Signups | 20 | 80 | 200 |
| Paying customers | 5 | 30 | 100 |
| MRR | $200 | $1,500 | $5,000+ |
| Trial → Paid conversion | 25% | 30% | 35% |
| Weekly email open rate | 60%+ | 55%+ | 50%+ |
| Monthly churn | <10% | <8% | <5% |
| Digest feedback (👍 rate) | 70%+ | 75%+ | 80%+ |

---

## Why This, Why Now

The intersection of cheap AI (GPT-4o-mini costs pennies per call), accessible scraping infrastructure (Outscraper does the hard work for $0.003/query), and an underserved market of med spa owners creates a window that didn't exist two years ago.

Med spas are booming — the medical aesthetics market is growing 12–15% annually. Every new med spa is another potential customer, and every existing one is flying blind on competitive intelligence. The big players (Podium, Birdeye) have moved upmarket to enterprise deals. Nobody is building the $29/month automated briefing for the independent med spa owner.

PulseTurf doesn't need to win the market. It needs **100 customers** to generate life-changing money. That's 100 med spas across the entire United States — a rounding error in a market of 10,000+ locations.

**The math is simple**: 3 weeks to build, $15/month to run, 95% margins, and a clear path to $5K MRR. Let's go. 🦈

---

*PRD v2.0 — Build-ready | Generated: 2026-03-03 | Drew 🦈, Chief of Staff*
