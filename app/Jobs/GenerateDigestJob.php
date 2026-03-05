<?php

namespace App\Jobs;

use App\Models\Digest;
use App\Models\User;
use App\Services\DigestGeneratorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];
    public $timeout = 120;

    public function __construct(public User $user) {}

    public function handle(DigestGeneratorService $service): void
    {
        $business = $this->user->business;

        if (! $business || $business->status !== 'active') {
            Log::warning('GenerateDigestJob: Skipping — user has no active business.', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Idempotency: skip if digest already exists for this week.
        // Use whereDate() so that the comparison works correctly across MySQL
        // and SQLite (which stores date columns as datetime strings).
        if (Digest::where('business_id', $business->id)->whereDate('week_start', $weekStart)->exists()) {
            Log::info('GenerateDigestJob: Digest already exists for this week, skipping.', [
                'business_id' => $business->id,
                'week_start' => $weekStart,
            ]);

            return;
        }

        $result = $service->generate($business);

        $digest = Digest::create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'week_start' => $weekStart,
            'subject_line' => $result->subjectLine,
            'html_content' => $result->content,
            'content_json' => $result->contentJson,
            'llm_model' => $result->model,
            'llm_prompt' => $result->prompt,
            'llm_response' => $result->rawResponse,
            'llm_tokens_used' => $result->tokensUsed,
            'llm_cost_cents' => $result->costCents,
            'status' => 'generated',
        ]);

        // Dispatch SendDigestJob with delay to Monday 7 AM in the user's timezone
        $userTimezone = $this->user->timezone ?? 'UTC';
        $sendAt = Carbon::parse($weekStart, $userTimezone)
            ->setTime(7, 0, 0)
            ->utc();

        SendDigestJob::dispatch($digest)->delay($sendAt);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateDigestJob failed.', [
            'user_id' => $this->user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
