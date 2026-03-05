<?php

namespace App\Console\Commands;

use App\Jobs\SendDigestJob;
use App\Models\Digest;
use App\Models\User;
use App\Services\DigestGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDigestCommand extends Command
{
    protected $signature = 'digest:generate {userId} {--send}';

    protected $description = 'Generate a digest for a specific user';

    public function handle(DigestGeneratorService $service): int
    {
        $userId = $this->argument('userId');
        $user = User::with('business')->find($userId);

        if (! $user) {
            $this->error("User #{$userId} not found.");

            return self::FAILURE;
        }

        if (! $user->business || $user->business->status !== 'active') {
            $this->error("User #{$userId} does not have an active business.");

            return self::FAILURE;
        }

        $business = $user->business;
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $this->info("Generating digest for {$user->email} (business: {$business->name})...");

        $result = $service->generate($business);

        $existing = Digest::where('business_id', $business->id)
            ->whereDate('week_start', $weekStart)
            ->first();

        $digest = Digest::updateOrCreate(
            [
                'business_id' => $business->id,
                'week_start' => $existing ? $existing->week_start : $weekStart,
            ],
            [
                'user_id' => $user->id,
                'subject_line' => $result->subjectLine,
                'html_content' => $result->content,
                'content_json' => $result->contentJson,
                'llm_model' => $result->model,
                'llm_prompt' => $result->prompt,
                'llm_response' => $result->rawResponse,
                'llm_tokens_used' => $result->tokensUsed,
                'llm_cost_cents' => $result->costCents,
                'status' => 'generated',
            ]
        );

        $this->info('Digest generated successfully.');
        $this->line("  Subject:    {$result->subjectLine}");
        $this->line('  Fallback:   ' . ($result->isFallback ? 'Yes (AI unavailable)' : 'No'));
        $this->line('  Tokens:     ' . ($result->tokensUsed ?? 'N/A'));
        $this->line('  Cost:       ' . ($result->costCents !== null ? '$' . number_format($result->costCents / 100, 4) : 'N/A'));
        $this->line("  Digest ID:  {$digest->id}");

        if ($this->option('send')) {
            $this->info('Sending digest now (--send flag provided)...');
            SendDigestJob::dispatchSync($digest);
            $this->info('Digest sent successfully.');
        }

        return self::SUCCESS;
    }
}
