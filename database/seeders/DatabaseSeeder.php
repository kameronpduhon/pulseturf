<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\Digest;
use App\Models\Review;
use App\Models\ScrapeLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'demo@pulseturf.com',
            'timezone' => 'America/Chicago',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $business = Business::factory()->active()->create([
            'user_id' => $user->id,
            'name' => 'Glow Aesthetics',
            'address' => '1234 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'zip' => '78701',
            'google_rating' => 4.8,
            'google_review_count' => 142,
            'google_categories' => ['Med Spa', 'Skin Care Clinic', 'Beauty Salon'],
        ]);

        $competitors = [
            ['name' => 'Radiance Med Spa', 'google_rating' => 4.5, 'google_review_count' => 98],
            ['name' => 'Luxe Skin Clinic', 'google_rating' => 4.3, 'google_review_count' => 67],
            ['name' => 'Serenity Aesthetics', 'google_rating' => 4.7, 'google_review_count' => 215],
        ];

        foreach ($competitors as $data) {
            $competitor = Competitor::factory()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'city' => 'Austin',
                'state' => 'TX',
                'google_rating' => $data['google_rating'],
                'google_review_count' => $data['google_review_count'],
            ]);

            Review::factory()
                ->count(20)
                ->sequence(fn ($sequence) => [
                    'reviewable_type' => Competitor::class,
                    'reviewable_id' => $competitor->id,
                ])
                ->create();

            ScrapeLog::factory()->successful()->create([
                'scrapeable_type' => Competitor::class,
                'scrapeable_id' => $competitor->id,
                'rating_at_scrape' => $data['google_rating'],
                'review_count_at_scrape' => $data['google_review_count'],
            ]);
        }

        Review::factory()
            ->count(20)
            ->sequence(fn ($sequence) => [
                'reviewable_type' => Business::class,
                'reviewable_id' => $business->id,
            ])
            ->create();

        ScrapeLog::factory()->successful()->create([
            'scrapeable_type' => Business::class,
            'scrapeable_id' => $business->id,
            'rating_at_scrape' => 4.8,
            'review_count_at_scrape' => 142,
        ]);

        // 2 sent digests for past weeks
        Digest::factory()->sent()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'week_start' => now()->startOfWeek()->subWeeks(2),
            'subject_line' => 'Glow Aesthetics: 3 new reviews, rating steady at 4.8',
        ]);

        Digest::factory()->sent()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'week_start' => now()->startOfWeek()->subWeek(),
            'subject_line' => 'Glow Aesthetics: Competitor "Serenity" gained 5 new reviews',
        ]);

        // 1 draft digest for current week
        Digest::factory()->draft()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'week_start' => now()->startOfWeek(),
            'subject_line' => 'Glow Aesthetics: Weekly Intelligence Report',
        ]);
    }
}
