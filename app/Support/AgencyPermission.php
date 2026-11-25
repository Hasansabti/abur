<?php

namespace App\Support;

class AgencyPermission
{
    public static function can(string $ability): bool
    {
        if (auth('agency')->check()) {
            return true;
        }

        if (!auth('employee')->check()) {
            return false;
        }

        $employee = auth('employee')->user();
        if (($employee->role ?? null) === 'admin') {
            return true;
        }

        $perms = $employee->permissions ?? [];
        if (is_string($perms)) {
            $perms = json_decode($perms, true) ?? [];
        }
        $perms = (array) $perms;

        if (in_array($ability, $perms, true)) {
            return true;
        }

        $pageMap = [
            'booking_passengers' => 'bookings',
            'booking_companions' => 'bookings',
            'booking_seats' => 'bookings',
            'booking_rooms' => 'rooms',
            'booking_custom_services' => 'bookings',
            'booking_prices' => 'bookings',
            'booking_discounts' => 'bookings',
            'manage_discount_codes' => 'finance',
            'confirm_unpaid' => 'bookings',
            'issue_invoices' => 'bookings',
            'print_invoices' => 'bookings',
            'print_receipts' => 'bookings',
            'add_balance' => 'finance',
            'send_sms' => 'bookings',
            'send_whatsapp' => 'bookings',
            'client_links' => 'bookings',
            'redeem_points' => 'finance',
            'refund_points' => 'finance',
            'view_change_logs' => 'bookings',
            'view_rooming_reports' => 'rooms',
            'view_meals_reports' => 'reports',
            'export_reports' => 'reports',
        ];

        $page = $pageMap[$ability] ?? $ability;

        return in_array($page, $perms, true);
    }

    public static function denyUnless(string $ability): void
    {
        abort_unless(self::can($ability), 403, 'عذراً، لا تملك صلاحية تنفيذ هذا الإجراء.');
    }
}
