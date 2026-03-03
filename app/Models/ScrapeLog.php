<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScrapeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrapeable_type',
        'scrapeable_id',
        'status',
        'source',
        'api_response_code',
        'error_message',
        'reviews_found',
        'new_reviews',
        'rating_at_scrape',
        'review_count_at_scrape',
        'cost_cents',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'rating_at_scrape' => 'decimal:1',
        ];
    }

    public function scrapeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
