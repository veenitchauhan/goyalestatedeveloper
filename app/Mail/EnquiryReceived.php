<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $enquiryId) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New website enquiry assigned to you');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.enquiry-received');
    }
}
