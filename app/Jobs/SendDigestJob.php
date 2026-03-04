<?php

namespace App\Jobs;

use App\Mail\DigestMail;
use App\Models\Digest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];
    public $timeout = 60;

    public function __construct(public Digest $digest) {}

    public function handle(): void
    {
        // Atomic idempotency: claim the digest for sending to prevent duplicate emails
        $claimed = Digest::where('id', $this->digest->id)
            ->where('status', '!=', 'sent')
            ->update(['status' => 'sending']);

        if ($claimed === 0) {
            Log::info('SendDigestJob: Digest already sent or being sent, skipping.', [
                'digest_id' => $this->digest->id,
            ]);

            return;
        }

        Mail::to($this->digest->business->user)->send(new DigestMail($this->digest));

        $this->digest->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendDigestJob failed.', [
            'digest_id' => $this->digest->id,
            'error' => $e->getMessage(),
        ]);

        $this->digest->update([
            'status' => 'failed',
        ]);
    }
}
