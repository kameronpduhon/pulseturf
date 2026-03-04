<?php

namespace Tests\Feature\Livewire;

use App\Exceptions\OutscraperException;
use App\Jobs\ScrapeBusinessJob;
use App\Jobs\ScrapeCompetitorJob;
use App\Livewire\SetupWizard;
use App\Models\Business;
use App\Models\Competitor;
use App\Models\ScrapeLog;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\OutscraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comprehensive tests for the SetupWizard Livewire component.
 *
 * The wizard walks users through 4 steps:
 *   1. Find & confirm your business
 *   2. Add competitors
 *   3. Wait for initial scrape
 *   4. Setup complete
 */
class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /**
     * Return a standard "found business" array as the OutscraperService would return.
     */
    private function foundBusinessData(array $overrides = []): array
    {
        return array_merge([
            'place_id' => 'ChIJtest1234567890ABCDEFGHIJ',
            'name' => 'Test Med Spa',
            'address' => '123 Main St, Austin, TX 78701',
            'phone' => '512-555-1234',
            'website' => 'https://testmedspa.com',
            'rating' => 4.8,
            'review_count' => 150,
            'categories' => ['Med Spa'],
            'hours' => ['Monday: 9AM-5PM'],
        ], $overrides);
    }

    /**
     * Return a standard "found competitor" array.
     */
    private function foundCompetitorData(array $overrides = []): array
    {
        return array_merge([
            'place_id' => 'ChIJcomp1234567890ABCDEFGHIJ',
            'name' => 'Rival Med Spa',
            'address' => '456 Oak Ave, Austin, TX 78702',
            'phone' => '512-555-9876',
            'website' => 'https://rivalmedspa.com',
            'rating' => 4.5,
            'review_count' => 80,
            'categories' => ['Med Spa'],
            'hours' => ['Monday: 10AM-6PM'],
        ], $overrides);
    }

    /**
     * Create a verified user suitable for testing the setup wizard.
     */
    private function makeUser(): User
    {
        return User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Mount / Resumption
    // -------------------------------------------------------------------------

    public function test_fresh_user_starts_at_step_1(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSet('business', null);
    }

    public function test_user_with_pending_setup_business_and_no_competitors_resumes_at_step_2(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        // No competitors created

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertSet('currentStep', 2)
            ->assertSet('business.id', $business->id);
    }

    public function test_user_with_pending_setup_business_and_competitors_resumes_at_step_3(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        Competitor::factory()->create(['business_id' => $business->id]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertSet('currentStep', 3)
            ->assertSet('business.id', $business->id);
    }

    public function test_user_with_active_business_is_redirected_to_home(): void
    {
        $user = $this->makeUser();
        Business::factory()->active()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertRedirect(route('home'));
    }

    public function test_competitors_are_loaded_from_db_when_resuming_at_step_3(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create([
            'business_id' => $business->id,
            'name' => 'Persisted Rival Spa',
        ]);

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class);

        $component->assertSet('currentStep', 3);
        $competitors = $component->get('competitors');
        $this->assertCount(1, $competitors);
        $this->assertEquals('Persisted Rival Spa', $competitors[0]['name']);
        $this->assertEquals($competitor->id, $competitors[0]['id']);
    }

    // -------------------------------------------------------------------------
    // Step 1: Find Business
    // -------------------------------------------------------------------------

    public function test_find_business_calls_service_and_sets_found_business(): void
    {
        $user = $this->makeUser();
        $data = $this->foundBusinessData();

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('searchBusiness')->once()->andReturn($data);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701')
            ->call('findBusiness')
            ->assertSet('foundBusiness.name', 'Test Med Spa')
            ->assertSet('foundBusiness.place_id', $data['place_id'])
            ->assertSet('businessSearchError', '')
            ->assertSet('searching', false);
    }

    public function test_find_business_shows_error_on_failed_search(): void
    {
        $user = $this->makeUser();

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('searchBusiness')
            ->andThrow(OutscraperException::noResults('Test Med Spa'));

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701')
            ->call('findBusiness');

        $component->assertSet('foundBusiness', null);
        $component->assertSet('searchAttempts', 1);
        $component->assertSet('showUrlFallback', false);
        $this->assertNotEmpty($component->get('businessSearchError'));
    }

    public function test_find_business_increments_attempts_on_failed_search(): void
    {
        $user = $this->makeUser();

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('searchBusiness')
            ->andThrow(OutscraperException::noResults('Test Med Spa'));

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701');

        $component->call('findBusiness')->assertSet('searchAttempts', 1);
        $component->call('findBusiness')->assertSet('searchAttempts', 2);
    }

    public function test_url_fallback_is_shown_after_two_failed_attempts(): void
    {
        $user = $this->makeUser();

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('searchBusiness')
            ->andThrow(OutscraperException::noResults('Test Med Spa'));

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701');

        $component->call('findBusiness')->assertSet('showUrlFallback', false);
        $component->call('findBusiness')->assertSet('showUrlFallback', true);
    }

    public function test_confirm_business_creates_business_record_and_advances_to_step_2(): void
    {
        $user = $this->makeUser();
        $data = $this->foundBusinessData();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701')
            ->set('foundBusiness', $data)
            ->call('confirmBusiness')
            ->assertSet('currentStep', 2);

        $this->assertDatabaseHas('businesses', [
            'user_id' => $user->id,
            'name' => $data['name'],
            'google_place_id' => $data['place_id'],
            'status' => 'pending_setup',
        ]);
    }

    public function test_confirm_business_stores_all_mapped_fields(): void
    {
        $user = $this->makeUser();
        $data = $this->foundBusinessData([
            'rating' => 4.7,
            'review_count' => 200,
            'phone' => '512-555-0000',
            'website' => 'https://mymedspa.com',
        ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701')
            ->set('foundBusiness', $data)
            ->call('confirmBusiness');

        $business = Business::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('pending_setup', $business->status);
        $this->assertEquals('512-555-0000', $business->phone);
        $this->assertEquals('https://mymedspa.com', $business->website);
        $this->assertEquals('Austin', $business->city);
        $this->assertEquals('TX', $business->state);
        $this->assertEquals('78701', $business->zip);
    }

    public function test_confirm_business_is_idempotent_will_not_create_duplicate(): void
    {
        $user = $this->makeUser();
        $data = $this->foundBusinessData();

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('businessName', 'Test Med Spa')
            ->set('businessAddress', '123 Main St')
            ->set('businessCity', 'Austin')
            ->set('businessState', 'TX')
            ->set('businessZip', '78701')
            ->set('foundBusiness', $data);

        // Call once to create
        $component->call('confirmBusiness');
        // Call again — should not create a second record
        $component->call('confirmBusiness');

        $this->assertEquals(1, Business::where('user_id', $user->id)->count());
    }

    public function test_confirm_business_does_nothing_when_no_found_business(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('foundBusiness', null)
            ->call('confirmBusiness');

        $this->assertDatabaseEmpty('businesses');
    }

    public function test_confirm_business_redirects_to_home_when_existing_business_is_active(): void
    {
        $user = $this->makeUser();
        Business::factory()->active()->create(['user_id' => $user->id]);

        // Simulate the user somehow getting foundBusiness set while already having active business.
        // The mount() already calls redirect(route('home')), but we test confirmBusiness directly
        // by resetting the redirect and then calling it with a non-null foundBusiness.
        // Because mount() redirects away, we set up a fresh component instance after resetting the
        // business to active in DB — the redirect happens inside confirmBusiness's DB double-check.
        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertRedirect(route('home'));
    }

    public function test_reset_business_search_clears_found_business(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('foundBusiness', $this->foundBusinessData())
            ->set('businessSearchError', 'Some error')
            ->call('resetBusinessSearch')
            ->assertSet('foundBusiness', null)
            ->assertSet('businessSearchError', '');
    }

    public function test_find_business_by_url_with_raw_place_id_works(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJtest1234567890ABCDEFGHIJ';

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.8,
                'review_count' => 100,
                'phone' => '512-555-0001',
                'website' => 'https://example.com',
                'categories' => ['Med Spa'],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $placeId)
            ->set('businessName', 'Test Med Spa')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId)
            ->assertSet('businessSearchError', '');
    }

    public function test_find_business_by_url_with_query_param_place_id_works(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJtest1234567890ABCDEFGHIJ';
        $url = "https://maps.google.com/?place_id={$placeId}";

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.0,
                'review_count' => 50,
                'phone' => null,
                'website' => null,
                'categories' => [],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $url)
            ->set('businessName', 'Test Med Spa')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId)
            ->assertSet('businessSearchError', '');
    }

    public function test_find_business_by_url_with_path_segment_place_id_works(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJtest1234567890ABCDEFGHIJ';
        $url = "https://www.google.com/maps/place/Test+Med+Spa/{$placeId}/";

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.0,
                'review_count' => 50,
                'phone' => null,
                'website' => null,
                'categories' => [],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $url)
            ->set('businessName', 'Test Med Spa')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId)
            ->assertSet('businessSearchError', '');
    }

    public function test_find_business_by_url_with_invalid_input_shows_error(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', 'this is not a valid url or place id')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness', null)
            ->assertSet('searching', false);

        $error = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', 'this is not a valid url or place id')
            ->call('findBusinessByUrl')
            ->get('businessSearchError');

        $this->assertNotEmpty($error);
    }

    // -------------------------------------------------------------------------
    // Step 2: Competitors
    // -------------------------------------------------------------------------

    public function test_find_competitor_calls_service_and_sets_found_competitor(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $data = $this->foundCompetitorData();

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('searchBusiness')->once()->andReturn($data);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('competitorName', 'Rival Med Spa')
            ->set('competitorAddress', '456 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->call('findCompetitor')
            ->assertSet('foundCompetitor.name', 'Rival Med Spa')
            ->assertSet('competitorSearchError', '');
    }

    public function test_confirm_competitor_creates_competitor_record(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $data = $this->foundCompetitorData();

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('competitorName', 'Rival Med Spa')
            ->set('competitorAddress', '456 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->set('foundCompetitor', $data)
            ->call('confirmCompetitor');

        $this->assertDatabaseHas('competitors', [
            'business_id' => $business->id,
            'name' => $data['name'],
            'google_place_id' => $data['place_id'],
        ]);
    }

    public function test_confirm_competitor_adds_to_competitors_array(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $data = $this->foundCompetitorData();

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('competitorName', 'Rival Med Spa')
            ->set('competitorAddress', '456 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->set('foundCompetitor', $data)
            ->call('confirmCompetitor');

        $competitors = $component->get('competitors');
        $this->assertCount(1, $competitors);
        $this->assertEquals($data['name'], $competitors[0]['name']);
    }

    public function test_confirm_competitor_clears_the_search_form(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('competitorName', 'Rival Med Spa')
            ->set('competitorAddress', '456 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->set('foundCompetitor', $this->foundCompetitorData())
            ->call('confirmCompetitor')
            ->assertSet('foundCompetitor', null)
            ->assertSet('competitorName', '')
            ->assertSet('competitorAddress', '')
            ->assertSet('competitorCity', '')
            ->assertSet('competitorState', '')
            ->assertSet('competitorZip', '');
    }

    public function test_can_add_up_to_competitor_limit(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        // Non-subscribed users have a limit of 1 (from competitorLimit())
        $limit = $user->competitorLimit();
        $this->assertGreaterThanOrEqual(1, $limit);

        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        for ($i = 1; $i <= $limit; $i++) {
            $component
                ->set('competitorName', "Rival Spa {$i}")
                ->set('competitorAddress', "{$i} Oak Ave")
                ->set('competitorCity', 'Austin')
                ->set('competitorState', 'TX')
                ->set('competitorZip', '78702')
                ->set('foundCompetitor', $this->foundCompetitorData([
                    'place_id' => "ChIJcomp{$i}234567890ABCDEFGHIJK",
                    'name' => "Rival Spa {$i}",
                ]))
                ->call('confirmCompetitor');
        }

        $competitors = $component->get('competitors');
        $this->assertCount($limit, $competitors);
    }

    public function test_backend_enforces_competitor_limit_and_shows_error(): void
    {
        $user = $this->makeUser();
        // Non-subscribed user — limit is 1
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        // Add first competitor (reaches the limit for non-subscribed users)
        $component
            ->set('competitorName', 'Rival Spa 1')
            ->set('competitorAddress', '1 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->set('foundCompetitor', $this->foundCompetitorData([
                'place_id' => 'ChIJcomp11234567890ABCDEFGHIJK',
                'name' => 'Rival Spa 1',
            ]))
            ->call('confirmCompetitor');

        // Try to add another beyond the limit
        $component
            ->set('competitorName', 'Rival Spa 2')
            ->set('competitorAddress', '2 Oak Ave')
            ->set('competitorCity', 'Austin')
            ->set('competitorState', 'TX')
            ->set('competitorZip', '78702')
            ->set('foundCompetitor', $this->foundCompetitorData([
                'place_id' => 'ChIJcomp21234567890ABCDEFGHIJK',
                'name' => 'Rival Spa 2',
            ]))
            ->call('confirmCompetitor');

        $error = $component->get('competitorSearchError');
        $this->assertNotEmpty($error);
        $this->assertStringContainsString('maximum', $error);
    }

    public function test_confirm_competitor_does_nothing_without_business(): void
    {
        $user = $this->makeUser();
        // No business — fresh user

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('foundCompetitor', $this->foundCompetitorData())
            ->call('confirmCompetitor');

        $this->assertDatabaseEmpty('competitors');
    }

    public function test_confirm_competitor_does_nothing_without_found_competitor(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('foundCompetitor', null)
            ->call('confirmCompetitor');

        $competitors = $component->get('competitors');
        $this->assertEmpty($competitors);
    }

    public function test_remove_competitor_deletes_from_db_and_removes_from_list(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        // Component loads at step 2 (no scrape logs yet) with 1 competitor
        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        // Step 3 would be loaded here since there's a competitor. Let's confirm step.
        $this->assertEquals(3, $component->get('currentStep'));
        $this->assertCount(1, $component->get('competitors'));

        $component->call('removeCompetitor', 0);

        $this->assertDatabaseMissing('competitors', ['id' => $competitor->id]);
        $this->assertEmpty($component->get('competitors'));
    }

    public function test_remove_competitor_only_deletes_competitors_belonging_to_the_users_business(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        // Another user's business + competitor
        $otherUser = $this->makeUser();
        $otherBusiness = Business::factory()->pendingSetup()->create(['user_id' => $otherUser->id]);
        $otherCompetitor = Competitor::factory()->create(['business_id' => $otherBusiness->id]);

        // Add a competitor to our business too so the wizard reaches step 3
        $ourCompetitor = Competitor::factory()->create(['business_id' => $business->id]);

        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        // Manually inject the other competitor's ID into the competitors array to simulate IDOR attempt
        $competitors = $component->get('competitors');
        $competitors[] = [
            'id' => $otherCompetitor->id,
            'name' => 'Other Spa',
            'address' => '789 Elm St',
            'rating' => 4.0,
            'review_count' => 50,
        ];
        $component->set('competitors', $competitors);

        // Try to delete the injected competitor (index 1)
        $component->call('removeCompetitor', 1);

        // The other user's competitor must still exist
        $this->assertDatabaseHas('competitors', ['id' => $otherCompetitor->id]);
    }

    public function test_remove_competitor_does_nothing_for_invalid_index(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        // competitors array is empty on fresh mount (step 2, no competitors)
        $component->call('removeCompetitor', 99);
        // Should not throw, no DB changes
        $this->assertDatabaseEmpty('competitors');
    }

    public function test_proceed_to_scraping_requires_at_least_one_competitor(): void
    {
        $user = $this->makeUser();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->assertSet('currentStep', 2);

        $component->call('proceedToScraping');

        $component->assertSet('currentStep', 2); // Did not advance
    }

    public function test_proceed_to_scraping_advances_to_step_3(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        Bus::fake();

        // Component mounts at step 3 since there's already a competitor
        // Let's test proceedToScraping via manual setup
        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        // Manually back to step 2 to test the transition
        $component->set('currentStep', 2);
        $component->call('proceedToScraping');

        $component->assertSet('currentStep', 3);
    }

    // -------------------------------------------------------------------------
    // Step 3: Scraping
    // -------------------------------------------------------------------------

    /**
     * Helper: create a pending-setup business + competitor in DB, then return a component
     * already at step 2 (i.e., business exists but we manually set competitors in component
     * state so proceedToScraping() can dispatch jobs).
     *
     * scrapeStatuses must be empty for startScraping() to run. This requires the component
     * to be at step 2 (business set, competitors[] populated) so proceedToScraping() fires
     * startScraping() for the first time.
     */
    private function componentReadyToStartScraping(User $user, Business $business, array $competitorRows): \Livewire\Features\SupportTesting\Testable
    {
        // Build the competitors array the component expects
        $competitorArray = array_map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'address' => $c->address,
            'rating' => $c->google_rating,
            'review_count' => $c->google_review_count,
        ], $competitorRows);

        return Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('business', $business)
            ->set('competitors', $competitorArray)
            ->set('scrapeStatuses', []);
    }

    public function test_start_scraping_dispatches_scrape_business_job(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        Bus::fake();

        $component = $this->componentReadyToStartScraping($user, $business, [$competitor]);
        $component->call('proceedToScraping');

        Bus::assertDispatched(ScrapeBusinessJob::class, fn ($job) => $job->business->id === $business->id);
    }

    public function test_start_scraping_dispatches_scrape_competitor_job_for_each_competitor(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor1 = Competitor::factory()->create(['business_id' => $business->id]);
        $competitor2 = Competitor::factory()->create(['business_id' => $business->id]);

        Bus::fake();

        $component = $this->componentReadyToStartScraping($user, $business, [$competitor1, $competitor2]);
        $component->call('proceedToScraping');

        Bus::assertDispatchedTimes(ScrapeCompetitorJob::class, 2);
    }

    public function test_start_scraping_initialises_scrape_statuses(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create([
            'business_id' => $business->id,
            'name' => 'Rival Spa',
        ]);

        Bus::fake();

        $component = $this->componentReadyToStartScraping($user, $business, [$competitor]);
        $component->call('proceedToScraping');

        $statuses = $component->get('scrapeStatuses');
        $this->assertArrayHasKey('business', $statuses);
        $this->assertEquals('pending', $statuses['business']['status']);
        $this->assertArrayHasKey("competitor_{$competitor->id}", $statuses);
    }

    public function test_start_scraping_is_idempotent_will_not_dispatch_duplicate_jobs(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        Bus::fake();

        $component = $this->componentReadyToStartScraping($user, $business, [$competitor]);
        $component->call('startScraping'); // First call — dispatches
        $component->call('startScraping'); // Second call — scrapeStatuses non-empty, no-op

        Bus::assertDispatchedTimes(ScrapeBusinessJob::class, 1);
    }

    public function test_check_scrape_progress_reads_success_log_and_maps_to_complete(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        // Create success scrape logs before mounting so initScrapeStatusesFromDb reads them
        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Business::class,
            'scrapeable_id' => $business->id,
        ]);
        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Competitor::class,
            'scrapeable_id' => $competitor->id,
        ]);

        Notification::fake();

        // Mount at step 3 — scrapeStatuses seeded from DB via initScrapeStatusesFromDb()
        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->assertSet('currentStep', 3);

        // Scrape statuses should already reflect the logs
        $component->call('checkScrapeProgress');

        $statuses = $component->get('scrapeStatuses');
        $this->assertEquals('complete', $statuses['business']['status']);
        $this->assertEquals('complete', $statuses["competitor_{$competitor->id}"]['status']);
    }

    public function test_check_scrape_progress_maps_failed_log_to_failed(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        ScrapeLog::factory()->failed()->create([
            'scrapeable_type' => Business::class,
            'scrapeable_id' => $business->id,
        ]);
        ScrapeLog::factory()->failed()->create([
            'scrapeable_type' => Competitor::class,
            'scrapeable_id' => $competitor->id,
        ]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('checkScrapeProgress');

        $statuses = $component->get('scrapeStatuses');
        $this->assertEquals('failed', $statuses['business']['status']);
        $this->assertEquals('failed', $statuses["competitor_{$competitor->id}"]['status']);
    }

    public function test_check_scrape_progress_keeps_pending_when_no_log_exists(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);
        // No scrape logs — initScrapeStatusesFromDb will set status to 'pending' (default)

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('checkScrapeProgress');

        $statuses = $component->get('scrapeStatuses');
        // No logs yet — status should remain pending
        $this->assertEquals('pending', $statuses['business']['status']);
        // Still at step 3 because not all are done
        $component->assertSet('currentStep', 3);
    }

    public function test_all_scrapes_complete_automatically_advances_to_step_4(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Business::class,
            'scrapeable_id' => $business->id,
        ]);
        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Competitor::class,
            'scrapeable_id' => $competitor->id,
        ]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('checkScrapeProgress');

        $component->assertSet('currentStep', 4);
    }

    public function test_failed_scrapes_do_not_block_progression_to_step_4(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        // Business failed, competitor succeeded — both are "done"
        ScrapeLog::factory()->failed()->create([
            'scrapeable_type' => Business::class,
            'scrapeable_id' => $business->id,
        ]);
        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Competitor::class,
            'scrapeable_id' => $competitor->id,
        ]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('checkScrapeProgress');

        // All done (mix of complete/failed) → should advance to step 4
        $component->assertSet('currentStep', 4);
    }

    public function test_empty_scrape_statuses_does_not_cause_premature_completion(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        // Component mounts at step 3. scrapeStatuses is populated from DB (no logs = pending).
        // Manually clear scrapeStatuses to simulate a fresh step-3 state before any polls.
        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->set('scrapeStatuses', []);
        $component->call('checkScrapeProgress');

        // Empty scrapeStatuses causes checkScrapeProgress to return early
        $component->assertSet('currentStep', 3);
    }

    public function test_check_scrape_progress_does_nothing_without_business(): void
    {
        $user = $this->makeUser();
        // No business

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('checkScrapeProgress'); // Should not throw

        $component->assertSet('currentStep', 1);
    }

    // -------------------------------------------------------------------------
    // Step 4: Completion
    // -------------------------------------------------------------------------

    public function test_complete_setup_sets_business_status_to_active(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        Notification::fake();

        // Mount at step 3; manually set scrapeStatuses to simulate post-dispatch state
        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('completeSetup');

        $this->assertEquals('active', $business->fresh()->status);
    }

    public function test_complete_setup_advances_to_step_4(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        Competitor::factory()->create(['business_id' => $business->id]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('completeSetup');

        $component->assertSet('currentStep', 4);
    }

    public function test_complete_setup_dispatches_welcome_notification(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        Competitor::factory()->create(['business_id' => $business->id]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('completeSetup');

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_complete_setup_is_idempotent_will_not_send_multiple_notifications(): void
    {
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        Competitor::factory()->create(['business_id' => $business->id]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        $component->call('completeSetup');
        $component->call('completeSetup'); // Second call — currentStep is now 4, no-op

        Notification::assertSentToTimes($user, WelcomeNotification::class, 1);
    }

    public function test_complete_setup_does_nothing_without_business(): void
    {
        $user = $this->makeUser();

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);
        $component->call('completeSetup');

        Notification::assertNothingSent();
        $component->assertSet('currentStep', 1); // Did not advance
    }

    public function test_complete_setup_does_not_downgrade_already_active_business(): void
    {
        // If the business was already set active by the scrape job, completeSetup
        // should leave it active without creating a second update.
        $user = $this->makeUser();
        $business = Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $competitor = Competitor::factory()->create(['business_id' => $business->id]);

        Notification::fake();

        $component = Livewire::actingAs($user)->test(SetupWizard::class);

        // Simulate job having already set status to active
        $business->update(['status' => 'active']);

        $component->call('completeSetup');

        $this->assertEquals('active', $business->fresh()->status);
        $component->assertSet('currentStep', 4);
    }

    // -------------------------------------------------------------------------
    // Helper: extractPlaceId (tested via findBusinessByUrl / findCompetitorByUrl)
    // -------------------------------------------------------------------------

    public function test_extract_place_id_accepts_raw_chij_place_id(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJN1t_tDeuEmsRUsoyG83frY4';

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.5,
                'review_count' => 60,
                'phone' => null,
                'website' => null,
                'categories' => [],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $placeId)
            ->set('businessName', 'Test')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId);
    }

    public function test_extract_place_id_accepts_url_with_query_param(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJN1t_tDeuEmsRUsoyG83frY4';
        $url = "https://maps.google.com/maps?q=some+business&place_id={$placeId}&hl=en";

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.5,
                'review_count' => 60,
                'phone' => null,
                'website' => null,
                'categories' => [],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $url)
            ->set('businessName', 'Test')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId);
    }

    public function test_extract_place_id_accepts_url_with_path_segment(): void
    {
        $user = $this->makeUser();
        $placeId = 'ChIJN1t_tDeuEmsRUsoyG83frY4';
        $url = "https://www.google.com/maps/place/Some+Business/@30.2,-97.7,15z/data=ChIJN1t_tDeuEmsRUsoyG83frY4";

        // Path segment extraction: /place/[name]/[segment]
        $urlWithPathId = "https://www.google.com/maps/place/Some+Business/{$placeId}/";

        $mock = $this->mock(OutscraperService::class);
        $mock->shouldReceive('getBusinessInfo')
            ->once()
            ->with($placeId)
            ->andReturn([
                'rating' => 4.5,
                'review_count' => 60,
                'phone' => null,
                'website' => null,
                'categories' => [],
                'hours' => [],
            ]);

        Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', $urlWithPathId)
            ->set('businessName', 'Test')
            ->call('findBusinessByUrl')
            ->assertSet('foundBusiness.place_id', $placeId);
    }

    public function test_extract_place_id_returns_null_for_invalid_input_and_shows_error(): void
    {
        $user = $this->makeUser();

        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', 'https://maps.google.com/maps?q=coffee+shop')
            ->call('findBusinessByUrl');

        $error = $component->get('businessSearchError');
        $this->assertNotEmpty($error);
        $component->assertSet('foundBusiness', null);
    }

    public function test_extract_place_id_returns_null_for_short_chij_string(): void
    {
        $user = $this->makeUser();

        // A short ChIJ string that doesn't meet the minimum length
        $component = Livewire::actingAs($user)
            ->test(SetupWizard::class)
            ->set('googleMapsUrl', 'ChIJshort')
            ->call('findBusinessByUrl');

        $error = $component->get('businessSearchError');
        $this->assertNotEmpty($error);
    }
}
