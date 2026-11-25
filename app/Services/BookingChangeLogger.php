<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingChangeLog;

class BookingChangeLogger
{
    public function log(
        Booking $booking,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        array $meta = []
    ): ?BookingChangeLog {
        $oldValues = $this->scrub($oldValues);
        $newValues = $this->scrub($newValues);

        if ($oldValues === $newValues && empty($meta['force'])) {
            return null;
        }

        return BookingChangeLog::create([
            'booking_id' => $booking->id,
            'agency_id' => $booking->agency_id ?? $meta['agency_id'] ?? null,
            'action' => $action,
            'entity_type' => $meta['entity_type'] ?? 'booking',
            'entity_id' => $meta['entity_id'] ?? $booking->id,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'reason' => $meta['reason'] ?? null,
            'performed_by' => $meta['performed_by'] ?? $this->currentActorName(),
            'actor_type' => $meta['actor_type'] ?? $this->currentActorType(),
            'source' => $meta['source'] ?? 'agency',
        ]);
    }

    private function currentActorName(): string
    {
        if (auth('employee')->check()) {
            return auth('employee')->user()->name;
        }
        if (auth('agency')->check()) {
            return auth('agency')->user()->name;
        }
        if (auth('admin')->check()) {
            return auth('admin')->user()->name ?? 'إدارة';
        }
        if (auth()->check()) {
            return auth()->user()->name;
        }

        return 'النظام';
    }

    private function currentActorType(): string
    {
        if (auth('employee')->check() || auth('agency')->check()) {
            return 'agency';
        }
        if (auth('admin')->check()) {
            return 'admin';
        }
        if (auth()->check()) {
            return 'client';
        }

        return 'system';
    }

    private function scrub(array $values): array
    {
        $sensitive = ['password', 'api_key', 'token', 'card_number', 'cvv', 'moyasar_payment_id'];
        foreach ($sensitive as $key) {
            unset($values[$key]);
        }

        if (isset($values['passenger_national_id']) && is_string($values['passenger_national_id']) && strlen($values['passenger_national_id']) >= 6) {
            $values['passenger_national_id'] = substr($values['passenger_national_id'], 0, 2) . '******' . substr($values['passenger_national_id'], -2);
        }

        return $values;
    }
}
