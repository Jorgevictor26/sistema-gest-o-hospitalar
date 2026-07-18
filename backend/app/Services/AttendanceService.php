<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Procedure;
use App\Repositories\AttendanceRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepository $attendances,
    ) {}

    public function create(array $data, int $registeredBy): Attendance
    {
        $procedures = Procedure::query()
            ->whereIn('id', $data['procedures'])
            ->get();

        $totalInCents = $procedures->sum(
            fn (Procedure $procedure): int => $this->toCents((string) $procedure->price)
        );
        $amountPaidInCents = $this->toCents((string) ($data['amount_paid'] ?? 0));

        if ($amountPaidInCents > $totalInCents) {
            throw ValidationException::withMessages([
                'amount_paid' => 'O valor pago não pode ser superior ao total do atendimento.',
            ]);
        }

        return DB::transaction(function () use ($data, $registeredBy, $procedures, $totalInCents, $amountPaidInCents): Attendance {
            $attendance = $this->attendances->create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'amount_paid' => $this->fromCents($amountPaidInCents),
                'total_amount' => $this->fromCents($totalInCents),
                'registered_by' => $registeredBy,
                'attendance_date' => $data['attendance_date'],
            ]);

            $attendance->procedures()->attach(
                $procedures->mapWithKeys(fn (Procedure $procedure): array => [
                    $procedure->id => ['price' => $procedure->price],
                ])->all()
            );

            return $attendance->load([
                'patient',
                'doctor.user',
                'registeredBy',
                'procedures',
            ]);
        });
    }

    private function toCents(string $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$units, $cents] = explode('.', $normalized);

        return ((int) $units * 100) + (int) $cents;
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
