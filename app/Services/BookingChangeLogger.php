<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingChangeLog;

class BookingChangeLogger
{
    public static function log(
        Booking $booking,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $entityType = null,
        $entityId = null,
        ?string $reason = null,
        ?string $source = 'agency'
    ): ?BookingChangeLog {
        $oldValues = self::sanitize($oldValues);
        $newValues = self::sanitize($newValues);

        if ($oldValues === $newValues && empty($reason) && $action !== 'issue_invoice') {
            return null;
        }

        $actor = self::actor();

        return BookingChangeLog::create([
            'booking_id' => $booking->id,
            'agency_id' => $booking->agency_id ?? $booking->trip?->agency_id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'reason' => $reason,
            'performed_by' => $actor['name'],
            'actor_type' => $actor['type'],
            'source' => $source,
        ]);
    }

    public static function actor(): array
    {
        if (auth('employee')->check()) {
            return ['name' => auth('employee')->user()->name, 'type' => 'agency_employee'];
        }
        if (auth('agency')->check()) {
            return ['name' => auth('agency')->user()->name, 'type' => 'agency'];
        }
        if (auth('admin')->check()) {
            return ['name' => auth('admin')->user()->name ?? 'إدارة', 'type' => 'admin'];
        }
        if (auth('web')->check()) {
            return ['name' => auth('web')->user()->name, 'type' => 'client'];
        }

        return ['name' => 'نظام', 'type' => 'system'];
    }

    protected static function sanitize(array $values): array
    {
        $hidden = ['password', 'api_key', 'secret', 'token', 'card_number', 'cvv', 'moyasar_payment_id'];
        foreach ($hidden as $key) {
            unset($values[$key]);
        }

        if (isset($values['passenger_national_id']) && !self::canSeeIdentity()) {
            $values['passenger_national_id'] = self::mask((string) $values['passenger_national_id']);
        }

        return $values;
    }

    public static function canSeeIdentity(): bool
    {
        return auth('agency')->check() || auth('admin')->check()
            || (auth('employee')->check() && (auth('employee')->user()->role === 'admin'));
    }

    public static function mask(string $value): string
    {
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . mb_substr($value, -4);
    }
}
