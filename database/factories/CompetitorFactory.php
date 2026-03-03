<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Competitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competitor>
 */
class CompetitorFactory extends Factory
{
    protected $model = Competitor::class;

    private static array $names = [
        'SkinPerfect Med Spa',
        'Renewal Aesthetics',
        'Elite Skin & Body',
        'Ageless Beauty Clinic',
        'Zen Med Spa',
        'The Beauty Lounge',
        'Derma Luxe Studio',
        'Fresh Face Aesthetics',
        'Rejuvenate Med Spa',
        'Velvet Skin Clinic',
    ];

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement(self::$names),
            'google_place_id' => 'ChIJ' . fake()->regexify('[A-Za-z0-9]{27}'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'google_rating' => fake()->randomFloat(1, 3.5, 5.0),
            'google_review_count' => fake()->numberBetween(10, 300),
            'google_categories' => ['Med Spa'],
            'google_hours' => null,
            'last_scraped_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ];
    }
}
