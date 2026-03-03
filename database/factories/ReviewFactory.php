<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    private static array $positiveTexts = [
        'Amazing experience! The staff was so friendly and professional. My skin looks incredible after the treatment.',
        'Best med spa in town! I\'ve been coming here for months and the results speak for themselves.',
        'Love this place! The Botox was painless and the results are so natural looking.',
        'The hydrafacial was absolutely wonderful. My skin has never felt so smooth and radiant.',
        'Five stars! The team really knows what they\'re doing. I always feel so pampered here.',
        'Incredible results from my laser treatment. The technician was knowledgeable and made me feel at ease.',
        'I\'ve tried other med spas but this one is by far the best. Clean, professional, and great results.',
        'The filler results look so natural! I\'m thrilled with how everything turned out.',
    ];

    private static array $negativeTexts = [
        'Very disappointed with my experience. The results were not what I expected and the staff seemed rushed.',
        'Waited over 30 minutes past my appointment time. The treatment was okay but the wait was unacceptable.',
        'Not worth the price. I\'ve had better results elsewhere for less money.',
        'The consultation felt rushed and I didn\'t feel like my concerns were fully addressed.',
    ];

    public function definition(): array
    {
        $rating = fake()->randomElement([5, 5, 5, 4, 4, 4, 3, 3, 2, 1]);
        $isPositive = $rating >= 4;

        return [
            'reviewable_type' => Business::class,
            'reviewable_id' => Business::factory(),
            'google_review_id' => fake()->unique()->uuid(),
            'author_name' => fake()->name(),
            'author_image' => null,
            'rating' => $rating,
            'text' => $isPositive
                ? fake()->randomElement(self::$positiveTexts)
                : fake()->randomElement(self::$negativeTexts),
            'published_at' => fake()->dateTimeBetween('-90 days', 'now'),
            'owner_response' => null,
            'owner_response_at' => null,
            'sentiment' => match (true) {
                $rating >= 4 => 'positive',
                $rating <= 2 => 'negative',
                default => 'neutral',
            },
            'sentiment_topics' => null,
        ];
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomElement([1, 2]),
            'text' => fake()->randomElement(self::$negativeTexts),
            'sentiment' => 'negative',
        ]);
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomElement([4, 5]),
            'text' => fake()->randomElement(self::$positiveTexts),
            'sentiment' => 'positive',
        ]);
    }

    public function withOwnerResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_response' => 'Thank you so much for your feedback! We appreciate you taking the time to share your experience with us.',
            'owner_response_at' => fake()->dateTimeBetween($attributes['published_at'] ?? '-30 days', 'now'),
        ]);
    }
}
