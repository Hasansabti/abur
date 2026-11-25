<?php

namespace App\Support;

class Money
{
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    public static function add(mixed ...$amounts): string
    {
        $total = '0.00';
        foreach ($amounts as $amount) {
            $total = bcadd($total, self::of($amount), 2);
        }

        return $total;
    }

    public static function sub(mixed $left, mixed $right): string
    {
        return bcsub(self::of($left), self::of($right), 2);
    }

    public static function mul(mixed $left, mixed $right): string
    {
        return bcmul(self::of($left), self::of($right), 2);
    }

    public static function max(mixed $left, mixed $right): string
    {
        return bccomp(self::of($left), self::of($right), 2) >= 0 ? self::of($left) : self::of($right);
    }

    public static function min(mixed $left, mixed $right): string
    {
        return bccomp(self::of($left), self::of($right), 2) <= 0 ? self::of($left) : self::of($right);
    }

    public static function gte(mixed $left, mixed $right): bool
    {
        return bccomp(self::of($left), self::of($right), 2) >= 0;
    }

    public static function gt(mixed $left, mixed $right): bool
    {
        return bccomp(self::of($left), self::of($right), 2) === 1;
    }

    public static function toFloat(mixed $value): float
    {
        return (float) self::of($value);
    }
}
