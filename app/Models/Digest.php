<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Digest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'week_start',
        'subject_line',
        'html_content',
        'plain_content',
        'llm_prompt',
        'llm_response',
        'llm_model',
        'llm_tokens_used',
        'llm_cost_cents',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeGenerated(Builder $query): Builder
    {
        return $query->where('status', 'generated');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
