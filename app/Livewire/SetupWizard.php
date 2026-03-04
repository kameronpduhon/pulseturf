<?php

namespace App\Livewire;

use App\Exceptions\OutscraperException;
use App\Jobs\ScrapeBusinessJob;
use App\Jobs\ScrapeCompetitorJob;
use App\Models\Business;
use App\Models\Competitor;
use App\Notifications\WelcomeNotification;
use App\Services\OutscraperService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class SetupWizard extends Component
{
    // -------------------------------------------------------------------------
    // Step tracking
    // -------------------------------------------------------------------------
    public int $currentStep = 1;

    // -------------------------------------------------------------------------
    // Step 1: Your Business
    // -------------------------------------------------------------------------
    #[Validate('required|string|max:255')]
    public string $businessName = '';

    #[Validate('required|string|max:255')]
    public string $businessAddress = '';

    #[Validate('required|string|max:100')]
    public string $businessCity = '';

    #[Validate('required|string|max:100')]
    public string $businessState = '';

    #[Validate('required|string|max:20')]
    public string $businessZip = '';

    public array|null $foundBusiness = null;
    public int $searchAttempts = 0;
    public bool $showUrlFallback = false;
    public string $googleMapsUrl = '';
    public bool $searching = false;
    public string $businessSearchError = '';

    // -------------------------------------------------------------------------
    // Step 2: Competitors
    // -------------------------------------------------------------------------
    #[Validate('required|string|max:255')]
    public string $competitorName = '';

    #[Validate('required|string|max:255')]
    public string $competitorAddress = '';

    #[Validate('required|string|max:100')]
    public string $competitorCity = '';

    #[Validate('required|string|max:100')]
    public string $competitorState = '';

    #[Validate('required|string|max:20')]
    public string $competitorZip = '';

    public array|null $foundCompetitor = null;
    public array $competitors = [];
    public int $competitorSearchAttempts = 0;
    public bool $showCompetitorUrlFallback = false;
    public string $competitorGoogleMapsUrl = '';
    public bool $searchingCompetitor = false;
    public string $competitorSearchError = '';

    // -------------------------------------------------------------------------
    // Step 3: Scraping
    // -------------------------------------------------------------------------
    public array $scrapeStatuses = [];

    // -------------------------------------------------------------------------
    // Shared state
    // -------------------------------------------------------------------------
    public Business|null $business = null;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $user = auth()->user();
        $business = $user->business()->where('status', 'pending_setup')->first();

        if ($business) {
            $this->business = $business;
            $competitorCount = $business->competitors()->count();

            if ($competitorCount === 0) {
                // Business exists but no competitors yet — resume at step 2
                $this->currentStep = 2;
            } else {
                // Business + competitors exist but scraping not triggered yet — go to step 3
                $this->currentStep = 3;
                $this->loadCompetitorsFromDb();
                $this->initScrapeStatusesFromDb();
            }
        } elseif ($user->hasActiveBusiness()) {
            // Already fully set up — send to home
            $this->redirect(route('home'));
        }
    }

    // -------------------------------------------------------------------------
    // Step 1: Find / confirm your business
    // -------------------------------------------------------------------------

    public function findBusiness(): void
    {
        $this->validateOnly('businessName');
        $this->validateOnly('businessAddress');
        $this->validateOnly('businessCity');
        $this->validateOnly('businessState');
        $this->validateOnly('businessZip');

        $this->businessSearchError = '';
        $this->foundBusiness = null;
        $this->searching = true;

        try {
            $service = app(OutscraperService::class);
            $result = $service->searchBusiness(
                $this->businessName,
                "{$this->businessAddress}, {$this->businessCity}, {$this->businessState} {$this->businessZip}"
            );
            $this->foundBusiness = $result;
        } catch (OutscraperException $e) {
            $this->searchAttempts++;
            $this->businessSearchError = 'We could not find that business. Please check the name and address and try again.';

            if ($this->searchAttempts >= 2) {
                $this->showUrlFallback = true;
            }
        } finally {
            $this->searching = false;
        }
    }

    public function confirmBusiness(): void
    {
        if (! $this->foundBusiness || $this->business) {
            return;
        }

        // Double-check against DB to prevent duplicate creation
        $existing = auth()->user()->business;
        if ($existing) {
            if ($existing->status === 'active') {
                $this->redirect(route('home'));
            } else {
                // Resume from where we left off
                $this->business = $existing;
                $this->currentStep = $existing->competitors()->exists() ? 3 : 2;
                if ($this->currentStep === 3) {
                    $this->loadCompetitorsFromDb();
                    $this->initScrapeStatusesFromDb();
                }
            }
            return;
        }

        $this->business = Business::create([
            'user_id' => auth()->id(),
            'name' => $this->foundBusiness['name'],
            'google_place_id' => $this->foundBusiness['place_id'],
            'address' => $this->foundBusiness['address'] ?? $this->businessAddress,
            'city' => $this->businessCity,
            'state' => $this->businessState,
            'zip' => $this->businessZip,
            'google_rating' => $this->foundBusiness['rating'],
            'google_review_count' => $this->foundBusiness['review_count'],
            'google_categories' => $this->foundBusiness['categories'],
            'google_hours' => $this->foundBusiness['hours'],
            'phone' => $this->foundBusiness['phone'],
            'website' => $this->foundBusiness['website'],
            'status' => 'pending_setup',
        ]);

        $this->currentStep = 2;
    }

    public function resetBusinessSearch(): void
    {
        $this->foundBusiness = null;
        $this->businessSearchError = '';
    }

    public function findBusinessByUrl(): void
    {
        $this->businessSearchError = '';
        $this->foundBusiness = null;
        $this->searching = true;

        $placeId = $this->extractPlaceId($this->googleMapsUrl);

        if (! $placeId) {
            $this->businessSearchError = 'Could not extract a Place ID from that input. Please paste the Place ID directly (e.g. ChIJ...).';
            $this->searching = false;

            return;
        }

        try {
            $service = app(OutscraperService::class);
            $info = $service->getBusinessInfo($placeId);

            $this->foundBusiness = array_merge(['place_id' => $placeId, 'name' => $this->businessName, 'address' => null], $info);
        } catch (OutscraperException $e) {
            $this->businessSearchError = 'Could not retrieve business details for that Place ID. Please verify it is correct.';
        } finally {
            $this->searching = false;
        }
    }

    // -------------------------------------------------------------------------
    // Step 2: Find / confirm competitors
    // -------------------------------------------------------------------------

    public function findCompetitor(): void
    {
        $this->validateOnly('competitorName');
        $this->validateOnly('competitorAddress');
        $this->validateOnly('competitorCity');
        $this->validateOnly('competitorState');
        $this->validateOnly('competitorZip');

        $this->competitorSearchError = '';
        $this->foundCompetitor = null;
        $this->searchingCompetitor = true;

        try {
            $service = app(OutscraperService::class);
            $result = $service->searchBusiness(
                $this->competitorName,
                "{$this->competitorAddress}, {$this->competitorCity}, {$this->competitorState} {$this->competitorZip}"
            );
            $this->foundCompetitor = $result;
        } catch (OutscraperException $e) {
            $this->competitorSearchAttempts++;
            $this->competitorSearchError = 'We could not find that competitor. Please check the name and address and try again.';

            if ($this->competitorSearchAttempts >= 2) {
                $this->showCompetitorUrlFallback = true;
            }
        } finally {
            $this->searchingCompetitor = false;
        }
    }

    public function confirmCompetitor(): void
    {
        if (! $this->foundCompetitor || ! $this->business) {
            return;
        }

        // Enforce backend competitor limit
        $limit = auth()->user()->competitorLimit();
        if (count($this->competitors) >= $limit) {
            $this->competitorSearchError = "You have reached the maximum of {$limit} competitors for your plan.";
            return;
        }

        $competitor = Competitor::create([
            'business_id' => $this->business->id,
            'name' => $this->foundCompetitor['name'],
            'google_place_id' => $this->foundCompetitor['place_id'],
            'address' => $this->foundCompetitor['address'] ?? $this->competitorAddress,
            'city' => $this->competitorCity,
            'state' => $this->competitorState,
            'zip' => $this->competitorZip,
            'google_rating' => $this->foundCompetitor['rating'],
            'google_review_count' => $this->foundCompetitor['review_count'],
            'google_categories' => $this->foundCompetitor['categories'],
            'google_hours' => $this->foundCompetitor['hours'],
            'phone' => $this->foundCompetitor['phone'],
            'website' => $this->foundCompetitor['website'],
        ]);

        $this->competitors[] = [
            'id' => $competitor->id,
            'name' => $competitor->name,
            'address' => $competitor->address,
            'rating' => $competitor->google_rating,
            'review_count' => $competitor->google_review_count,
        ];

        // Clear form for adding another
        $this->foundCompetitor = null;
        $this->competitorName = '';
        $this->competitorAddress = '';
        $this->competitorCity = '';
        $this->competitorState = '';
        $this->competitorZip = '';
        $this->competitorSearchAttempts = 0;
        $this->showCompetitorUrlFallback = false;
        $this->competitorGoogleMapsUrl = '';
        $this->competitorSearchError = '';
    }

    public function removeCompetitor(int $index): void
    {
        if (! isset($this->competitors[$index]) || ! $this->business) {
            return;
        }

        $competitorId = $this->competitors[$index]['id'];

        // Verify ownership before deleting (prevents IDOR)
        $deleted = $this->business->competitors()
            ->where('id', $competitorId)
            ->delete();

        if ($deleted) {
            array_splice($this->competitors, $index, 1);
        }
    }

    public function findCompetitorByUrl(): void
    {
        $this->competitorSearchError = '';
        $this->foundCompetitor = null;
        $this->searchingCompetitor = true;

        $placeId = $this->extractPlaceId($this->competitorGoogleMapsUrl);

        if (! $placeId) {
            $this->competitorSearchError = 'Could not extract a Place ID from that input. Please paste the Place ID directly (e.g. ChIJ...).';
            $this->searchingCompetitor = false;

            return;
        }

        try {
            $service = app(OutscraperService::class);
            $info = $service->getBusinessInfo($placeId);

            $this->foundCompetitor = array_merge(['place_id' => $placeId, 'name' => $this->competitorName, 'address' => null], $info);
        } catch (OutscraperException $e) {
            $this->competitorSearchError = 'Could not retrieve competitor details for that Place ID. Please verify it is correct.';
        } finally {
            $this->searchingCompetitor = false;
        }
    }

    public function proceedToScraping(): void
    {
        if (empty($this->competitors) || ! $this->business) {
            return;
        }

        $this->currentStep = 3;
        $this->startScraping();
    }

    // -------------------------------------------------------------------------
    // Step 3: Scraping
    // -------------------------------------------------------------------------

    public function startScraping(): void
    {
        if (! $this->business || ! empty($this->scrapeStatuses)) {
            return;
        }

        // Initialise status tracking
        $this->scrapeStatuses = [
            'business' => ['name' => $this->business->name, 'status' => 'pending'],
        ];

        foreach ($this->competitors as $competitor) {
            $this->scrapeStatuses['competitor_' . $competitor['id']] = [
                'name' => $competitor['name'],
                'status' => 'pending',
            ];
        }

        // Dispatch jobs
        ScrapeBusinessJob::dispatch($this->business);

        $competitorModels = $this->business->competitors;
        foreach ($competitorModels as $competitor) {
            ScrapeCompetitorJob::dispatch($competitor);
        }
    }

    public function checkScrapeProgress(): void
    {
        if (! $this->business || empty($this->scrapeStatuses)) {
            return;
        }

        // Check business scrape log
        $businessLog = $this->business->scrapeLogs()->latest()->first();
        if ($businessLog) {
            $this->scrapeStatuses['business']['status'] = $this->mapLogStatus($businessLog->status);
        }

        // Eager load competitors with their latest scrape log to avoid N+1
        $competitors = $this->business->competitors()->with([
            'scrapeLogs' => fn ($q) => $q->latest()->limit(1),
        ])->get();

        foreach ($competitors as $competitor) {
            $key = 'competitor_' . $competitor->id;
            $log = $competitor->scrapeLogs->first();
            if ($log && isset($this->scrapeStatuses[$key])) {
                $this->scrapeStatuses[$key]['status'] = $this->mapLogStatus($log->status);
            }
        }

        // Check if all are done (complete or failed — not still pending)
        $allDone = collect($this->scrapeStatuses)->every(
            fn ($item) => in_array($item['status'], ['complete', 'failed'])
        );

        if ($allDone) {
            $this->completeSetup();
        }
    }

    // -------------------------------------------------------------------------
    // Step 4: Complete
    // -------------------------------------------------------------------------

    public function completeSetup(): void
    {
        if (! $this->business || $this->currentStep >= 4) {
            return;
        }

        // Reload business to get latest data
        $this->business->refresh();

        // If scraping marked it active already, great. Otherwise mark it active now.
        if ($this->business->status !== 'active') {
            $this->business->update(['status' => 'active']);
        }

        $this->currentStep = 4;

        // Send welcome notification (queued automatically via ShouldQueue on WelcomeNotification)
        auth()->user()->notify(new WelcomeNotification($this->business));
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        return view('livewire.setup-wizard');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function extractPlaceId(string $input): ?string
    {
        $input = trim($input);

        // If it looks like a Google Place ID (typically starts with ChIJ)
        if (preg_match('/^ChIJ[A-Za-z0-9_\-]{20,}$/', $input)) {
            return $input;
        }

        // Try to extract from ?place_id= query parameter
        if (preg_match('/[?&]place_id=([^&]+)/i', $input, $m)) {
            $candidate = urldecode($m[1]);
            if (preg_match('/^ChIJ[A-Za-z0-9_\-]{20,}$/', $candidate)) {
                return $candidate;
            }
        }

        // Try to extract from URL path segment after /place/
        if (preg_match('|/place/[^/]+/([^/?]+)|', $input, $m)) {
            $segment = $m[1];
            if (str_starts_with($segment, 'ChIJ')) {
                return $segment;
            }
        }

        return null;
    }

    private function loadCompetitorsFromDb(): void
    {
        if (! $this->business) {
            return;
        }

        $this->competitors = $this->business->competitors->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'address' => $c->address,
            'rating' => $c->google_rating,
            'review_count' => $c->google_review_count,
        ])->toArray();
    }

    private function initScrapeStatusesFromDb(): void
    {
        if (! $this->business) {
            return;
        }

        $businessLog = $this->business->scrapeLogs()->latest()->first();
        $this->scrapeStatuses = [
            'business' => [
                'name' => $this->business->name,
                'status' => $this->mapLogStatus($businessLog?->status),
            ],
        ];

        // Eager load competitors with their latest scrape log to avoid N+1
        $competitors = $this->business->competitors()->with([
            'scrapeLogs' => fn ($q) => $q->latest()->limit(1),
        ])->get();

        foreach ($competitors as $competitor) {
            $log = $competitor->scrapeLogs->first();
            $this->scrapeStatuses['competitor_' . $competitor->id] = [
                'name' => $competitor->name,
                'status' => $this->mapLogStatus($log?->status),
            ];
        }
    }

    private function mapLogStatus(?string $status): string
    {
        return match ($status) {
            'success' => 'complete',
            'failed' => 'failed',
            default => 'pending',
        };
    }
}
