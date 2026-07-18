<?php

use App\Models\Attendance;

test('attendance derives its financial values consistently', function (): void {
    $attendance = new Attendance([
        'total_amount' => '1000.00',
        'amount_paid' => '250.00',
        'commission_percentage' => '60.00',
    ]);

    expect($attendance->pendingAmount())->toBe(750.0)
        ->and($attendance->paymentStatus())->toBe('partial')
        ->and($attendance->commissionAmount())->toBe(600.0);
});

test('attendance payment status covers unpaid and paid values', function (): void {
    $attendance = new Attendance(['total_amount' => '1000.00', 'amount_paid' => '0.00']);
    expect($attendance->paymentStatus())->toBe('unpaid');

    $attendance->amount_paid = '1000.00';
    expect($attendance->paymentStatus())->toBe('paid');
});
