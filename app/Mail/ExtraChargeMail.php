<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\BookingExtraCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExtraChargeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingExtraCharge $charge
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "طلب دفع إضافي للحجز #BK-{$this->booking->id}",
        );
    }

    public function content(): Content
{
    return new Content(
        view: 'emails.extra_charge',
        with: [
            'payUrl' => route('client.extra_charge.pay', $this->charge->token),
        ],
    );
}
}