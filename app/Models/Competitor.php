<?php

namespace App\Models;

use App\Models\Concerns\HasGoogleBusinessData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Competitor extends Model
{
    use HasFactory, HasGoogleBusinessData;

    protected $fillable = [
        'business_id',
        'name',
        'google_place_id',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'website',
        'google_rating',
        'google_review_count',
        'google_categories',
        'google_hours',
        'last_scraped_at',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function scrapeLogs(): MorphMany
    {
        return $this->morphMany(ScrapeLog::class, 'scrapeable');
    }
}
