<?php

use App\Support\Money;

test('money values are converted without duplicating rounding logic', function (): void {
    expect(Money::toCents('12500.50'))->toBe(1_250_050)
        ->and(Money::fromCents(1_250_050))->toBe('12500.50')
        ->and(Money::format('10'))->toBe('10.00');
});
