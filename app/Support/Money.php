<?php

namespace App\Support;

class Money
{
    public static function toCents(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function add(mixed ...$amounts): string
    {
        $cents = 0;
        foreach ($amounts as $amount) {
            $cents += self::toCents($amount);
        }

        return self::fromCents($cents);
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return self::fromCents(self::toCents($a) - self::toCents($b));
    }

    public static function mul(mixed $a, mixed $b): string
    {
        return self::fromCents((int) round(self::toCents($a) * (float) $b));
    }

    public static function maxZero(mixed $amount): string
    {
        return self::fromCents(max(0, self::toCents($amount)));
    }

    public static function min(mixed $a, mixed $b): string
    {
        return self::fromCents(min(self::toCents($a), self::toCents($b)));
    }

    public static function percent(mixed $amount, mixed $percent): string
    {
        return self::fromCents((int) round(self::toCents($amount) * ((float) $percent) / 100));
    }

    public static function toFloat(mixed $amount): float
    {
        return round((float) $amount, 2);
    }
}
