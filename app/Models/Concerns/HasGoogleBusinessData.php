<?php

namespace App\Models\Concerns;

trait HasGoogleBusinessData
{
    public function initializeHasGoogleBusinessData(): void
    {
        $this->mergeCasts([
            'google_categories' => 'array',
            'google_hours' => 'array',
            'google_rating' => 'decimal:1',
            'last_scraped_at' => 'datetime',
        ]);
    }
}
