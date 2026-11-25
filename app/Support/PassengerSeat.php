<?php

namespace App\Support;

class PassengerSeat
{
    public static function isExtra(mixed $seat): bool
    {
        if (!$seat) {
            return false;
        }

        $data = is_array($seat) ? $seat : [
            'is_extra' => $seat->is_extra ?? false,
            'parent_seat_id' => $seat->parent_seat_id ?? null,
            'room_type' => $seat->room_type ?? '',
            'passenger_name' => $seat->passenger_name ?? '',
        ];

        if (!empty($data['is_extra'])) {
            return true;
        }

        if (!empty($data['parent_seat_id'])) {
            return true;
        }

        $roomType = strtolower(trim((string) ($data['room_type'] ?? '')));
        if ($roomType === 'additional' || str_contains($roomType, 'إضافي') || str_contains($roomType, 'اضافي')) {
            return true;
        }

        $name = (string) ($data['passenger_name'] ?? '');

        return str_contains($name, 'مقعد إضافي');
    }

    public static function isActualGuest(mixed $seat): bool
    {
        if (!$seat) {
            return false;
        }

        $name = is_array($seat) ? ($seat['passenger_name'] ?? '') : ($seat->passenger_name ?? '');
        $roomType = is_array($seat) ? ($seat['room_type'] ?? '') : ($seat->room_type ?? '');

        if (str_contains((string) $name, 'مغلق') || str_contains((string) $name, 'حظر') || str_contains((string) $name, 'إغلاق')) {
            return false;
        }

        if (strtolower((string) $roomType) === 'blocked') {
            return false;
        }

        return !self::isExtra($seat);
    }

    public static function bedLabel(?string $bedType): string
    {
        return match ($bedType) {
            'single_bed', 'single' => 'سرير مفرد',
            'double_bed', 'double' => 'سرير مزدوج',
            'twin_beds' => 'سريران منفردان',
            'three_singles' => 'ثلاثة أسرّة فردية',
            'one_double_one_single' => 'سرير مزدوج + سرير فردي',
            'four_singles' => 'أربعة أسرّة فردية',
            'two_doubles' => 'سريران مزدوجان',
            'one_double_two_singles' => 'سرير مزدوج + سريران فرديان',
            default => $bedType ?: '—',
        };
    }
}
