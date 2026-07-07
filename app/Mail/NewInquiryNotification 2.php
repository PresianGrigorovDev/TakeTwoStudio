<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewInquiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;

    public function __construct(\App\Models\Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ново Контактно Запитване - Take Two Studio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new_inquiry',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
