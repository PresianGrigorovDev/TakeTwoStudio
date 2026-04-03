<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Резервацията ви е потвърдена - Take Two Studio 1603',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking_confirmed');
    }

    public function attachments(): array
    {
        return [];
    }
}
