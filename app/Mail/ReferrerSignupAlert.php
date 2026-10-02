<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferrerSignupAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $referrer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New referrer application');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.referrer-signup-alert');
    }
}
