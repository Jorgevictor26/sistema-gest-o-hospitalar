<?php

namespace App\Services;

use App\DTOs\AttendanceDTO;
use App\Models\Attendance;
use App\Models\AttendanceEdit;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\Procedure;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepository $attendances,
        private readonly DailyClosureService $closures,
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return $this->attendances->paginate($filters, $user);
    }

    public function create(AttendanceDTO $attendanceData, int $registeredBy): Attendance
    {
        $data = $attendanceData->values;
        $this->closures->ensureOpen($data['attendance_date']);

        $procedures = Procedure::query()
            ->whereIn('id', $data['procedures'])
            ->get();

        $totalInCents = $procedures->sum(
            fn (Procedure $procedure): int => Money::toCents($procedure->price)
        );
        $amountPaidInCents = Money::toCents($data['amount_paid'] ?? 0);

        if ($amountPaidInCents > $totalInCents) {
            throw ValidationException::withMessages([
                'amount_paid' => 'O valor pago não pode ser superior ao total do atendimento.',
            ]);
        }

        return DB::transaction(function () use ($data, $registeredBy, $procedures, $totalInCents, $amountPaidInCents): Attendance {
            $doctor = Doctor::query()->lockForUpdate()->findOrFail($data['doctor_id']);

            $attendance = $this->attendances->create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'amount_paid' => Money::fromCents($amountPaidInCents),
                'total_amount' => Money::fromCents($totalInCents),
                'commission_percentage' => $doctor->commission_percentage,
                'registered_by' => $registeredBy,
                'attendance_date' => $data['attendance_date'],
            ]);

            $attendance->procedures()->attach(
                $procedures->mapWithKeys(fn (Procedure $procedure): array => [
                    $procedure->id => ['price' => $procedure->price],
                ])->all()
            );

            if ($amountPaidInCents > 0) {
                Payment::create([
                    'attendance_id' => $attendance->id,
                    'amount' => Money::fromCents($amountPaidInCents),
                    'method' => $data['payment_method'],
                    'reference' => $data['payment_reference'] ?? null,
                    'notes' => 'Pagamento inicial do atendimento.',
                    'received_by' => $registeredBy,
                    'paid_at' => now(),
                ]);
            }

            return $attendance->load([
                'patient',
                'doctor.user',
                'registeredBy',
                'procedures',
            ]);
        });
    }

    public function update(Attendance $attendance, AttendanceDTO $attendanceData, int $editedBy): Attendance
    {
        $data = $attendanceData->values;
        $this->closures->ensureOpen($attendance->attendance_date);

        if (isset($data['attendance_date'])) {
            $this->closures->ensureOpen($data['attendance_date']);
        }

        return DB::transaction(function () use ($attendance, $data, $editedBy): Attendance {
            $attendance->load('procedures');
            $before = $this->snapshot($attendance);
            $procedurePrices = $this->procedurePrices($attendance, $data['procedures'] ?? null);
            $totalInCents = collect($procedurePrices)->sum(
                fn (array $pivot): int => Money::toCents($pivot['price'])
            );
            $amountPaidInCents = Money::toCents($attendance->amount_paid);

            if ($amountPaidInCents > $totalInCents) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'O valor pago não pode ser superior ao total do atendimento.',
                ]);
            }

            $this->attendances->update($attendance, [
                'patient_id' => $data['patient_id'] ?? $attendance->patient_id,
                'doctor_id' => $data['doctor_id'] ?? $attendance->doctor_id,
                'amount_paid' => Money::fromCents($amountPaidInCents),
                'total_amount' => Money::fromCents($totalInCents),
                'attendance_date' => $data['attendance_date'] ?? $attendance->attendance_date,
            ]);

            if (array_key_exists('procedures', $data)) {
                $attendance->procedures()->sync($procedurePrices);
            }

            $attendance->refresh()->load('procedures');
            $after = $this->snapshot($attendance);

            if ($before === $after) {
                throw ValidationException::withMessages([
                    'attendance' => 'Os dados enviados não alteram o atendimento.',
                ]);
            }

            AttendanceEdit::create([
                'attendance_id' => $attendance->id,
                'edited_by' => $editedBy,
                'reason' => $data['reason'],
                'old_values' => $before,
                'new_values' => $after,
            ]);

            return $attendance->load([
                'patient',
                'doctor.user',
                'registeredBy',
                'procedures',
                'edits.editor',
            ]);
        });
    }

    public function delete(Attendance $attendance, int $deletedBy, string $reason): void
    {
        $this->closures->ensureOpen($attendance->attendance_date);

        DB::transaction(function () use ($attendance, $deletedBy, $reason): void {
            $attendance->load('procedures');
            $before = $this->snapshot($attendance);

            AttendanceEdit::create([
                'attendance_id' => $attendance->id,
                'edited_by' => $deletedBy,
                'reason' => $reason,
                'old_values' => $before,
                'new_values' => [...$before, 'deleted' => true],
            ]);

            $this->attendances->delete($attendance);
        });
    }

    /**
     * @param  array<int, int>|null  $requestedProcedureIds
     * @return array<int, array{price: mixed}>
     */
    private function procedurePrices(Attendance $attendance, ?array $requestedProcedureIds): array
    {
        if ($requestedProcedureIds === null) {
            return $attendance->procedures->mapWithKeys(fn (Procedure $procedure): array => [
                $procedure->id => ['price' => $procedure->pivot->price],
            ])->all();
        }

        $existingPrices = $attendance->procedures->mapWithKeys(fn (Procedure $procedure): array => [
            $procedure->id => $procedure->pivot->price,
        ]);

        return Procedure::query()
            ->whereIn('id', $requestedProcedureIds)
            ->get()
            ->mapWithKeys(fn (Procedure $procedure): array => [
                $procedure->id => ['price' => $existingPrices->get($procedure->id, $procedure->price)],
            ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Attendance $attendance): array
    {
        return [
            'patient_id' => $attendance->patient_id,
            'doctor_id' => $attendance->doctor_id,
            'amount_paid' => $attendance->amount_paid,
            'total_amount' => $attendance->total_amount,
            'commission_percentage' => $attendance->commission_percentage,
            'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
            'procedures' => $attendance->procedures
                ->map(fn (Procedure $procedure): array => [
                    'id' => $procedure->id,
                    'price' => $procedure->pivot->price,
                ])
                ->sortBy('id')
                ->values()
                ->all(),
        ];
    }
}
