<?php

namespace App\Models;

use App\Models\Concerns\HasGoogleBusinessData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Business extends Model
{
    use HasFactory, HasGoogleBusinessData;

    protected $fillable = [
        'user_id',
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
        'status',
        'last_scraped_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function scrapeLogs(): MorphMany
    {
        return $this->morphMany(ScrapeLog::class, 'scrapeable');
    }

    public function digests(): HasMany
    {
        return $this->hasMany(Digest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePendingSetup(Builder $query): Builder
    {
        return $query->where('status', 'pending_setup');
    }
}
