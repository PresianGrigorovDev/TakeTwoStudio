<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRejectedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Относно вашата заявка за резервация - Take Two Studio 1603',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking_rejected');
    }

    public function attachments(): array
    {
        return [];
    }
}
