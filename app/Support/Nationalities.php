<?php

namespace App\Support;

class Nationalities
{
    public static function all(): array
    {
        return config('nationalities', []);
    }

    public static function label(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        foreach (self::all() as $item) {
            if (($item['code'] ?? '') === $code) {
                return $item['name_ar'] ?? $item['name_en'] ?? $code;
            }
        }

        return $code;
    }

    public static function isValid(?string $code): bool
    {
        if (!$code) {
            return true;
        }

        return collect(self::all())->contains(fn ($item) => ($item['code'] ?? '') === $code);
    }
}
