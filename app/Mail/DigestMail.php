<?php

namespace App\Mail;

use App\Models\Digest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class DigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Digest $digest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            subject: $this->digest->subject_line,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.digest',
            with: [
                'digest'      => $this->digest,
                'business'    => $this->digest->business,
                'positiveUrl' => URL::signedRoute('digest.feedback', [
                    'digest' => $this->digest->id,
                    'type'   => 'positive',
                ]),
                'negativeUrl' => URL::signedRoute('digest.feedback', [
                    'digest' => $this->digest->id,
                    'type'   => 'negative',
                ]),
            ],
        );
    }
}
