<?php

namespace Tests\Feature;

use App\Jobs\GenerateDigestJob;
use App\Jobs\SendDigestJob;
use App\Mail\DigestMail;
use App\Models\Business;
use App\Models\Competitor;
use App\Models\Digest;
use App\Models\Review;
use App\Models\User;
use App\Services\DigestGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DigestPipelineTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Decode an OpenAI fixture file and return its array representation.
     */
    private function openaiFixture(string $name): array
    {
        return json_decode(
            file_get_contents(base_path("tests/Fixtures/openai/{$name}.json")),
            true,
        );
    }

    /**
     * Build a fake OpenAI HTTP response using the chat-completion fixture.
     */
    private function fakeOpenAISuccess(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(
                $this->openaiFixture('chat-completion'),
                200,
            ),
        ]);
    }

    /**
     * Create a fully active user (trial active + active business).
     */
    private function makeEligibleUser(): User
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);

        Business::factory()->active()->create(['user_id' => $user->id]);

        return $user->load('business');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the OpenAI key is present for every test by default.
        config(['services.openai.key' => 'test-openai-key']);
    }

    // ---------------------------------------------------------------------------
    // DigestGeneratorService Tests
    // ---------------------------------------------------------------------------

    public function test_service_generates_digest_via_openai(): void
    {
        $this->fakeOpenAISuccess();

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        $this->assertFalse($result->isFallback);
        $this->assertSame('Your Weekly Med Spa Intel: 4.8★ Rating Holds Strong', $result->subjectLine);
        $this->assertStringContainsString('Performance Snapshot', $result->content);
        $this->assertStringContainsString('Competitor Watch', $result->content);
        $this->assertIsArray($result->contentJson);
        $this->assertArrayHasKey('performance_snapshot', $result->contentJson);
        $this->assertArrayHasKey('review_highlights', $result->contentJson);
        $this->assertArrayHasKey('competitor_watch', $result->contentJson);
        $this->assertArrayHasKey('sentiment_trends', $result->contentJson);
        $this->assertArrayHasKey('action_items', $result->contentJson);
        $this->assertArrayHasKey('week_ahead', $result->contentJson);
        $this->assertSame('gpt-4o-mini', $result->model);
        $this->assertSame(1270, $result->tokensUsed);
        $this->assertNotNull($result->rawResponse);
        $this->assertNotNull($result->prompt);
        $this->assertIsInt($result->costCents);
    }

    public function test_service_falls_back_when_openai_returns_500(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response('Internal Server Error', 500),
        ]);

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        $this->assertTrue($result->isFallback);
        $this->assertStringContainsString('Performance Snapshot', $result->content);
        $this->assertStringContainsString('Action Items', $result->content);
        $this->assertStringContainsString('Competitor Watch', $result->content);
        $this->assertIsArray($result->contentJson);
        $this->assertArrayHasKey('performance_snapshot', $result->contentJson);
        $this->assertArrayHasKey('action_items', $result->contentJson);
        $this->assertNull($result->model);
        $this->assertNull($result->tokensUsed);
    }

    public function test_service_falls_back_when_openai_rate_limited(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(
                $this->openaiFixture('chat-completion-error'),
                429,
            ),
        ]);

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        $this->assertTrue($result->isFallback);
        $this->assertStringContainsString('Performance Snapshot', $result->content);
        $this->assertIsArray($result->contentJson);
    }

    public function test_service_falls_back_when_api_key_missing(): void
    {
        // The missing key causes callOpenAI() to throw OpenAIException::missingApiKey(),
        // which generate() catches and falls back gracefully.
        config(['services.openai.key' => null]);

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        $this->assertTrue($result->isFallback);
    }

    public function test_service_strips_markdown_fences_from_response(): void
    {
        // Some LLMs wrap JSON in ``` fences even when told not to.
        $fixture = $this->openaiFixture('chat-completion');
        $innerContent = $fixture['choices'][0]['message']['content'];

        // Wrap content in markdown fences the way a misbehaving LLM might.
        $fixture['choices'][0]['message']['content'] = "```json\n{$innerContent}\n```";

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response($fixture, 200),
        ]);

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        // The result should still parse correctly with isFallback = false.
        $this->assertFalse($result->isFallback);
        $this->assertNotEmpty($result->subjectLine);
        $this->assertNotEmpty($result->content);
    }

    public function test_service_sanitizes_html_tags_from_ai_response(): void
    {
        // Craft an AI response that embeds HTML/XSS payloads in section values.
        // The service strips all HTML tags from section values since they must be plain text.
        $aiContent = json_encode([
            'subject_line' => 'Weekly Intel',
            'performance_snapshot' => 'Good week! <script>alert("xss")</script> Rating is <b>4.8</b>',
            'review_highlights' => 'Great reviews this week.',
            'competitor_watch' => 'Competitors are steady.',
            'sentiment_trends' => 'Positive trend.',
            'action_items' => '• Respond to reviews.',
            'week_ahead' => 'Keep it up!',
        ]);

        $fixture = $this->openaiFixture('chat-completion');
        $fixture['choices'][0]['message']['content'] = $aiContent;

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response($fixture, 200),
        ]);

        $business = Business::factory()->active()->create();
        $service  = app(DigestGeneratorService::class);

        $result = $service->generate($business);

        $this->assertFalse($result->isFallback);
        // HTML tags must be stripped from section values.
        $this->assertStringNotContainsString('<script>', $result->contentJson['performance_snapshot']);
        $this->assertStringNotContainsString('<b>', $result->contentJson['performance_snapshot']);
        // Plain text content must survive.
        $this->assertStringContainsString('Good week!', $result->contentJson['performance_snapshot']);
        $this->assertStringContainsString('Rating is 4.8', $result->contentJson['performance_snapshot']);
    }

    // ---------------------------------------------------------------------------
    // GenerateDigestJob Tests
    // ---------------------------------------------------------------------------

    public function test_generate_digest_job_creates_digest_record(): void
    {
        $this->fakeOpenAISuccess();
        // Prevent SendDigestJob from being dispatched synchronously so it does
        // not mutate the digest status before we assert on it.
        Queue::fake();

        $user = $this->makeEligibleUser();

        (new GenerateDigestJob($user))->handle(app(DigestGeneratorService::class));

        $this->assertDatabaseHas('digests', [
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'status'      => 'generated',
        ]);

        // Verify the correct week_start via the model (avoids SQLite date format mismatch).
        $digest = Digest::where('business_id', $user->business->id)->first();
        $this->assertNotNull($digest);
        $this->assertEquals(
            Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $digest->week_start->toDateString(),
        );
    }

    public function test_generate_digest_job_skips_if_digest_exists_for_current_week(): void
    {
        $this->fakeOpenAISuccess();
        Queue::fake();

        $user      = $this->makeEligibleUser();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Pre-create a digest for the current week.
        Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'week_start'  => $weekStart,
        ]);

        (new GenerateDigestJob($user))->handle(app(DigestGeneratorService::class));

        // Still exactly one digest — the job must have short-circuited.
        $this->assertDatabaseCount('digests', 1);
    }

    public function test_generate_digest_job_dispatches_send_digest_job(): void
    {
        $this->fakeOpenAISuccess();

        // We need SendDigestJob to be dispatched with a delay to the queue.
        // Use Queue::fake() so we can assert on the queued job.
        Queue::fake();

        $user = $this->makeEligibleUser();

        (new GenerateDigestJob($user))->handle(app(DigestGeneratorService::class));

        Queue::assertPushed(SendDigestJob::class, function (SendDigestJob $job) use ($user) {
            return $job->digest->user_id === $user->id;
        });
    }

    public function test_generate_digest_job_skips_when_business_is_inactive(): void
    {
        $this->fakeOpenAISuccess();
        Queue::fake();

        $user = User::factory()->create(['trial_ends_at' => now()->addDays(14)]);
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);
        $user->load('business');

        (new GenerateDigestJob($user))->handle(app(DigestGeneratorService::class));

        $this->assertDatabaseCount('digests', 0);
    }

    public function test_generate_digest_job_skips_when_user_has_no_business(): void
    {
        Queue::fake();

        $user = User::factory()->create(['trial_ends_at' => now()->addDays(14)]);

        (new GenerateDigestJob($user))->handle(app(DigestGeneratorService::class));

        $this->assertDatabaseCount('digests', 0);
    }

    // ---------------------------------------------------------------------------
    // SendDigestJob Tests
    // ---------------------------------------------------------------------------

    public function test_send_digest_job_sends_mail_and_marks_status_sent(): void
    {
        Mail::fake();

        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        (new SendDigestJob($digest))->handle();

        Mail::assertSent(DigestMail::class, function (DigestMail $mail) use ($digest) {
            return $mail->digest->id === $digest->id;
        });

        $this->assertDatabaseHas('digests', [
            'id'     => $digest->id,
            'status' => 'sent',
        ]);

        $this->assertNotNull($digest->fresh()->sent_at);
    }

    public function test_send_digest_job_skips_when_digest_is_already_sent(): void
    {
        Mail::fake();

        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->sent()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        (new SendDigestJob($digest))->handle();

        // The atomic guard checks for status != 'sent', so no mail should be sent.
        Mail::assertNotSent(DigestMail::class);
    }

    public function test_send_digest_job_marks_failed_when_mail_throws(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        // Make Mail::to()->send() throw so we can verify the failed() handler.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $job = new SendDigestJob($digest);

        try {
            $job->handle();
        } catch (\RuntimeException) {
            // Expected — simulate what the queue worker does after a throw.
        }

        // The failed() callback is called by the queue worker after all retries.
        $job->failed(new \RuntimeException('SMTP connection refused'));

        $this->assertDatabaseHas('digests', [
            'id'     => $digest->id,
            'status' => 'failed',
        ]);
    }

    // ---------------------------------------------------------------------------
    // WeeklyDigestCommand Tests
    // ---------------------------------------------------------------------------

    public function test_weekly_digest_command_queues_pipeline_for_eligible_users(): void
    {
        Bus::fake();

        $user       = $this->makeEligibleUser();
        $competitor = Competitor::factory()->create(['business_id' => $user->business->id]);

        $this->artisan('digest:weekly')->assertExitCode(0);

        Bus::assertChained([
            \App\Jobs\ScrapeBusinessJob::class,
            \App\Jobs\ScrapeCompetitorJob::class,
            GenerateDigestJob::class,
        ]);
    }

    public function test_weekly_digest_command_skips_users_with_existing_digest_for_this_week(): void
    {
        Bus::fake();

        $user      = $this->makeEligibleUser();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Pre-create a digest for this week — the command should skip this business.
        Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'week_start'  => $weekStart,
        ]);

        $this->artisan('digest:weekly')->assertExitCode(0);

        Bus::assertNothingDispatched();
    }

    public function test_weekly_digest_command_skips_users_with_expired_trial_and_no_subscription(): void
    {
        Bus::fake();

        // Expired trial, no subscription.
        $user = User::factory()->expired()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $this->artisan('digest:weekly')->assertExitCode(0);

        Bus::assertNothingDispatched();
    }

    public function test_weekly_digest_command_skips_users_with_no_active_business(): void
    {
        Bus::fake();

        $user = User::factory()->create(['trial_ends_at' => now()->addDays(14)]);
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $this->artisan('digest:weekly')->assertExitCode(0);

        Bus::assertNothingDispatched();
    }

    public function test_weekly_digest_command_queues_multiple_eligible_users(): void
    {
        Bus::fake();

        $userA = $this->makeEligibleUser();
        $userB = $this->makeEligibleUser();

        $this->artisan('digest:weekly')->assertExitCode(0);

        // Bus::chain() dispatches the first job in the chain; each chain results in
        // one ScrapeBusinessJob dispatch. Verify both pipelines were queued.
        Bus::assertDispatchedTimes(\App\Jobs\ScrapeBusinessJob::class, 2);
    }

    // ---------------------------------------------------------------------------
    // GenerateDigestCommand Tests
    // ---------------------------------------------------------------------------

    public function test_generate_digest_command_creates_digest_and_exits_successfully(): void
    {
        $this->fakeOpenAISuccess();

        $user = $this->makeEligibleUser();

        $this->artisan("digest:generate {$user->id}")
            ->assertExitCode(0)
            ->expectsOutputToContain('Digest generated successfully');

        $this->assertDatabaseHas('digests', [
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'status'      => 'generated',
        ]);
    }

    public function test_generate_digest_command_exits_with_failure_for_invalid_user(): void
    {
        $this->artisan('digest:generate 999999')
            ->assertExitCode(1)
            ->expectsOutputToContain('not found');
    }

    public function test_generate_digest_command_exits_with_failure_for_user_without_active_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $this->artisan("digest:generate {$user->id}")
            ->assertExitCode(1)
            ->expectsOutputToContain('does not have an active business');
    }

    public function test_generate_digest_command_with_send_flag_delivers_mail(): void
    {
        $this->fakeOpenAISuccess();
        Mail::fake();

        $user = $this->makeEligibleUser();

        $this->artisan("digest:generate {$user->id} --send")
            ->assertExitCode(0)
            ->expectsOutputToContain('Digest sent successfully');

        Mail::assertSent(DigestMail::class);
    }

    public function test_generate_digest_command_overwrites_existing_digest_for_same_week(): void
    {
        $this->fakeOpenAISuccess();

        $user      = $this->makeEligibleUser();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Pre-create a digest for the same week.
        Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'week_start'  => $weekStart,
            'subject_line' => 'Old subject',
        ]);

        $this->artisan("digest:generate {$user->id}")->assertExitCode(0);

        // updateOrCreate — still only one digest for the week.
        $this->assertDatabaseCount('digests', 1);

        $digest = Digest::first();
        $this->assertNotSame('Old subject', $digest->subject_line);
    }

    // ---------------------------------------------------------------------------
    // DigestMail Tests
    // ---------------------------------------------------------------------------

    public function test_digest_mail_uses_digest_subject_as_envelope_subject(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
            'subject_line' => 'Your Weekly Med Spa Intel: Rating Update',
        ]);

        $mailable = new DigestMail($digest);
        $envelope = $mailable->envelope();

        $this->assertSame('Your Weekly Med Spa Intel: Rating Update', $envelope->subject);
    }

    public function test_digest_mail_renders_html_content_and_feedback_urls(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        $mailable = new DigestMail($digest);
        $rendered = $mailable->render();

        // The subject line appears as a heading in the rendered body.
        $this->assertStringContainsString($digest->subject_line, $rendered);

        // The digest has content_json, so the styled email partial is used.
        $this->assertStringContainsString('Performance Snapshot', $rendered);
        $this->assertStringContainsString('4.8-star rating', $rendered);

        // Both positive and negative signed feedback URLs must appear in the rendered email.
        $this->assertStringContainsString('/digest/' . $digest->id . '/feedback/positive', $rendered);
        $this->assertStringContainsString('/digest/' . $digest->id . '/feedback/negative', $rendered);
    }

    // ---------------------------------------------------------------------------
    // DigestFeedbackController Tests
    // ---------------------------------------------------------------------------

    public function test_feedback_route_records_positive_feedback(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        $url = URL::signedRoute('digest.feedback', [
            'digest' => $digest->id,
            'type'   => 'positive',
        ]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertViewIs('feedback.thanks');

        $this->assertDatabaseHas('digests', [
            'id'       => $digest->id,
            'feedback' => 'positive',
        ]);
    }

    public function test_feedback_route_records_negative_feedback(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        $url = URL::signedRoute('digest.feedback', [
            'digest' => $digest->id,
            'type'   => 'negative',
        ]);

        $response = $this->get($url);

        $response->assertOk();

        $this->assertDatabaseHas('digests', [
            'id'       => $digest->id,
            'feedback' => 'negative',
        ]);
    }

    public function test_feedback_route_returns_404_for_invalid_type(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        $url = URL::signedRoute('digest.feedback', [
            'digest' => $digest->id,
            'type'   => 'invalid',
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_feedback_route_rejects_unsigned_request(): void
    {
        $user   = $this->makeEligibleUser();
        $digest = Digest::factory()->generated()->create([
            'user_id'     => $user->id,
            'business_id' => $user->business->id,
        ]);

        // Visit the route without a signature — the `signed` middleware should deny it.
        $this->get("/digest/{$digest->id}/feedback/positive")->assertForbidden();
    }

    // ---------------------------------------------------------------------------
    // Factory & Model Tests
    // ---------------------------------------------------------------------------

    public function test_digest_generated_factory_state_sets_correct_fields(): void
    {
        $digest = Digest::factory()->generated()->create();

        $this->assertSame('generated', $digest->status);
        $this->assertNotEmpty($digest->subject_line);
        $this->assertNotEmpty($digest->html_content);
        $this->assertSame('gpt-4o-mini', $digest->llm_model);
        $this->assertNotNull($digest->llm_prompt);
        $this->assertNotNull($digest->llm_response);
        $this->assertNotNull($digest->llm_tokens_used);
        $this->assertNotNull($digest->llm_cost_cents);
    }

    public function test_digest_failed_factory_state_sets_status_to_failed(): void
    {
        $digest = Digest::factory()->failed()->create();

        $this->assertSame('failed', $digest->status);
    }

    public function test_digest_sent_factory_state_sets_status_and_sent_at(): void
    {
        $digest = Digest::factory()->sent()->create();

        $this->assertSame('sent', $digest->status);
        $this->assertNotNull($digest->sent_at);
    }

    public function test_digest_scope_generated_returns_only_generated_digests(): void
    {
        Digest::factory()->generated()->create();
        Digest::factory()->generated()->create();
        Digest::factory()->sent()->create();
        Digest::factory()->failed()->create();

        $results = Digest::generated()->get();

        $this->assertCount(2, $results);
        $results->each(fn (Digest $d) => $this->assertSame('generated', $d->status));
    }

    public function test_digest_scope_failed_returns_only_failed_digests(): void
    {
        Digest::factory()->failed()->create();
        Digest::factory()->generated()->create();
        Digest::factory()->sent()->create();

        $results = Digest::failed()->get();

        $this->assertCount(1, $results);
        $this->assertSame('failed', $results->first()->status);
    }

    public function test_digest_scope_sent_returns_only_sent_digests(): void
    {
        Digest::factory()->sent()->create();
        Digest::factory()->sent()->create();
        Digest::factory()->generated()->create();

        $results = Digest::sent()->get();

        $this->assertCount(2, $results);
        $results->each(fn (Digest $d) => $this->assertSame('sent', $d->status));
    }

    // ---------------------------------------------------------------------------
    // Schedule Test
    // ---------------------------------------------------------------------------

    public function test_weekly_digest_command_is_scheduled_on_sundays(): void
    {
        $this->artisan('schedule:list', ['--json' => true])
            ->assertExitCode(0);

        // Parse the schedule list output directly from artisan.
        // The presence of digest:weekly in the schedule is verified by asserting
        // that the console.php definition executes without error.
        // We validate that the Sunday schedule is registered.
        $events = \Illuminate\Support\Facades\Schedule::events();

        $digestEvent = collect($events)->first(
            fn ($event) => str_contains($event->command, 'digest:weekly')
        );

        $this->assertNotNull($digestEvent, 'digest:weekly should be registered in the scheduler');
        // The expression for "every Sunday at midnight" in cron is: 0 0 * * 0
        $this->assertSame('0 0 * * 0', $digestEvent->expression);
    }
}
