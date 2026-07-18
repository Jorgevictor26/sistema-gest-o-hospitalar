<?php

use App\Models\Appointment;

test('appointment state transitions follow the configured workflow', function (string $current, string $target, bool $allowed) {
    $appointment = new Appointment(['status' => $current]);

    expect($appointment->canTransitionTo($target))->toBe($allowed);
})->with([
    'scheduled to confirmed' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED, true],
    'scheduled to cancelled' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CANCELLED, true],
    'scheduled to completed' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED, true],
    'scheduled to no show' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_NO_SHOW, true],
    'confirmed to cancelled' => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_CANCELLED, true],
    'confirmed to completed' => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED, true],
    'confirmed to no show' => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_NO_SHOW, true],
    'confirmed to scheduled' => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_SCHEDULED, false],
    'cancelled is terminal' => [Appointment::STATUS_CANCELLED, Appointment::STATUS_SCHEDULED, false],
    'completed is terminal' => [Appointment::STATUS_COMPLETED, Appointment::STATUS_CONFIRMED, false],
    'no show is terminal' => [Appointment::STATUS_NO_SHOW, Appointment::STATUS_CONFIRMED, false],
]);
