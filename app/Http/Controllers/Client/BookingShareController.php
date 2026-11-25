<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingAccessToken;

class BookingShareController extends Controller
{
    public function show(string $token)
    {
        $record = BookingAccessToken::with(['booking.trip', 'booking.bookingSeats', 'booking.invoices', 'booking.payments'])
            ->where('token', $token)
            ->first();

        if (!$record || !$record->isValid()) {
            abort(404, 'الرابط غير صالح أو منتهي.');
        }

        $booking = $record->booking;
        $guests = $booking->bookingSeats->filter(fn ($s) => $s->isActualGuest());

        return view('client.booking.share', [
            'booking' => $booking,
            'guests' => $guests,
            'token' => $record,
        ]);
    }
}
