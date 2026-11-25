<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\AccessTokenService;
use App\Support\PassengerSeat;

class BookingPortalController extends Controller
{
    public function show(string $token, AccessTokenService $tokens)
    {
        $access = $tokens->findValid($token);
        $booking = $access->booking()->with(['trip', 'bookingSeats.room', 'invoice', 'customServices', 'payments'])->firstOrFail();

        $guests = $booking->bookingSeats->filter(fn ($s) => PassengerSeat::isActualGuest($s));
        $summary = $booking->paymentSummary();

        return view('client.booking.portal', compact('booking', 'guests', 'summary', 'access'));
    }
}
