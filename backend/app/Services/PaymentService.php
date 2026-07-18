<?php

namespace App\Services;

use App\DTOs\PaymentDTO;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PaymentService
{
    public function __construct(private readonly DailyClosureService $closures) {}

    /**
     * @return Collection<int, Payment>
     */
    public function list(Attendance $attendance): Collection
    {
        return $attendance->payments()
            ->with(['receiver', 'voidedBy'])
            ->get();
    }

    public function create(Attendance $attendance, PaymentDTO $paymentData, User $receiver): Payment
    {
        $data = $paymentData->values;
        $this->closures->ensureOpen($attendance->attendance_date);

        return DB::transaction(function () use ($attendance, $data, $receiver): Payment {
            $lockedAttendance = Attendance::query()->lockForUpdate()->findOrFail($attendance->id);
            $paymentInCents = Money::toCents($data['amount']);
            $paidInCents = Money::toCents($lockedAttendance->amount_paid);
            $totalInCents = Money::toCents($lockedAttendance->total_amount);

            if ($paymentInCents > $totalInCents - $paidInCents) {
                throw ValidationException::withMessages([
                    'amount' => 'O pagamento não pode ser superior ao saldo pendente.',
                ]);
            }

            $payment = Payment::create([
                'attendance_id' => $lockedAttendance->id,
                'amount' => Money::fromCents($paymentInCents),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $receiver->id,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $lockedAttendance->update([
                'amount_paid' => Money::fromCents($paidInCents + $paymentInCents),
            ]);

            return $payment->load('receiver');
        });
    }

    public function void(Payment $payment, string $reason, User $admin): Payment
    {
        $payment->loadMissing('attendance');
        $this->closures->ensureOpen($payment->attendance->attendance_date);

        return DB::transaction(function () use ($payment, $reason, $admin): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->voided_at !== null) {
                throw new ConflictHttpException('Este pagamento já foi anulado.');
            }

            $attendance = Attendance::query()->lockForUpdate()->findOrFail($lockedPayment->attendance_id);

            $lockedPayment->update([
                'voided_by' => $admin->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $validPaymentsInCents = Payment::query()
                ->where('attendance_id', $attendance->id)
                ->whereNull('voided_at')
                ->get('amount')
                ->sum(fn (Payment $payment): int => Money::toCents($payment->amount));

            $attendance->update([
                'amount_paid' => Money::fromCents($validPaymentsInCents),
            ]);

            return $lockedPayment->load(['receiver', 'voidedBy']);
        });
    }
}
