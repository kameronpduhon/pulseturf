<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'google_review_id',
        'author_name',
        'author_image',
        'rating',
        'text',
        'published_at',
        'owner_response',
        'owner_response_at',
        'sentiment',
        'sentiment_topics',
    ];

    protected function casts(): array
    {
        return [
            'sentiment_topics' => 'array',
            'published_at' => 'datetime',
            'owner_response_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    public function scopeNegative(Builder $query): Builder
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopePositive(Builder $query): Builder
    {
        return $query->where('rating', '>=', 4);
    }
}
