<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    private static array $names = [
        'Glow Aesthetics',
        'Radiance Med Spa',
        'Luxe Skin Clinic',
        'Revive Beauty Bar',
        'Serenity Aesthetics',
        'Bella Vita Med Spa',
        'Pure Glow Skin Studio',
        'Elevate Med Spa',
        'Bloom Aesthetics',
        'Luminous Med Spa',
    ];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(self::$names),
            'google_place_id' => null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'google_rating' => fake()->randomFloat(1, 3.5, 5.0),
            'google_review_count' => fake()->numberBetween(10, 300),
            'google_categories' => ['Med Spa', 'Skin Care Clinic'],
            'google_hours' => null,
            'status' => 'pending_setup',
            'last_scraped_at' => null,
        ];
    }

    public function pendingSetup(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_setup',
            'google_place_id' => null,
            'last_scraped_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'google_place_id' => 'ChIJ' . fake()->regexify('[A-Za-z0-9]{27}'),
            'last_scraped_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }
}
