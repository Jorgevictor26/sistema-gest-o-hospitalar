<?php

namespace App\Support;

final class Money
{
    public static function toCents(int|float|string $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$units, $cents] = explode('.', $normalized);

        return ((int) $units * 100) + (int) $cents;
    }

    public static function fromCents(int $amount): string
    {
        return self::format($amount / 100);
    }

    public static function format(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
