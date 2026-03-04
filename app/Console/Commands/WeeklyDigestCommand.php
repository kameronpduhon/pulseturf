<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDigestJob;
use App\Jobs\ScrapeBusinessJob;
use App\Jobs\ScrapeCompetitorJob;
use App\Models\Digest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class WeeklyDigestCommand extends Command
{
    protected $signature = 'digest:weekly';

    protected $description = 'Run the weekly digest pipeline for all eligible users';

    public function handle(): int
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Load IDs of businesses that already have a digest for this week.
        // Use whereDate() so the comparison works on both MySQL and SQLite.
        $processedBusinessIds = Digest::whereDate('week_start', $weekStart)
            ->pluck('business_id')
            ->toArray();

        // Query eligible users: active business AND (on trial OR subscribed)
        $users = User::with(['business.competitors'])
            ->whereHas('business', fn ($q) => $q->where('status', 'active'))
            ->where(function ($query) {
                $query->where('trial_ends_at', '>', now())
                    ->orWhereHas('subscriptions', fn ($q) => $q->where('stripe_status', 'active'));
            })
            ->get();

        $processed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $business = $user->business;

            if (! $business) {
                $skipped++;
                continue;
            }

            // Skip if digest already exists for this week
            if (in_array($business->id, $processedBusinessIds, true)) {
                $this->line("Skipping user #{$user->id} ({$user->email}) — digest already exists for this week.");
                $skipped++;
                continue;
            }

            $competitorJobs = $business->competitors
                ->map(fn ($competitor) => new ScrapeCompetitorJob($competitor))
                ->all();

            Bus::chain([
                new ScrapeBusinessJob($business),
                ...$competitorJobs,
                new GenerateDigestJob($user),
            ])->dispatch();

            $this->info("Queued digest pipeline for user #{$user->id} ({$user->email}).");
            $processed++;
        }

        $this->info("Done. Processed: {$processed} users, Skipped: {$skipped} users.");

        return self::SUCCESS;
    }
}
