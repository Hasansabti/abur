<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAccessToken;
use Illuminate\Support\Str;

class AccessTokenService
{
    public function issueOrReuse(Booking $booking): BookingAccessToken
    {
        $existing = BookingAccessToken::where('booking_id', $booking->id)
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $days = (int) config('booking.client_link_ttl_days', 30);
        $token = BookingAccessToken::create([
            'booking_id' => $booking->id,
            'agency_id' => $booking->agency_id ?? $booking->trip?->agency_id,
            'token' => Str::random(48),
            'expires_at' => $days > 0 ? now()->addDays($days) : null,
            'created_by' => BookingChangeLogger::actor()['name'],
        ]);

        BookingChangeLogger::log($booking, 'create_client_link', [], [
            'expires_at' => optional($token->expires_at)?->toDateTimeString(),
        ], 'access_token', $token->id);

        return $token;
    }

    public function revoke(Booking $booking): void
    {
        BookingAccessToken::where('booking_id', $booking->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        BookingChangeLogger::log($booking, 'revoke_client_link', [], ['revoked' => true], 'access_token');
    }

    public function findValid(string $token): BookingAccessToken
    {
        $record = BookingAccessToken::where('token', $token)->firstOrFail();
        abort_unless($record->isValid(), 403, 'رابط الحجز غير صالح أو منتهٍ.');
        $record->update(['last_accessed_at' => now()]);

        return $record;
    }

    public function url(BookingAccessToken $token): string
    {
        return url('/b/' . $token->token);
    }
}
