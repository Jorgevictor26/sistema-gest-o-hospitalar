<?php

namespace App\Services;

use App\Models\DailyClosure;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DailyClosureService
{
    public function __construct(private readonly AttendanceReportService $reportService) {}

    public function close(string $date, User $user): DailyClosure
    {
        try {
            return DB::transaction(function () use ($date, $user): DailyClosure {
                if ($this->isClosed($date)) {
                    throw new ConflictHttpException('Este dia já está fechado.');
                }

                $summary = $this->reportService->generate([
                    'period' => 'daily',
                    'date' => $date,
                ]);

                return DailyClosure::create([
                    'date' => $date,
                    'active_date' => $date,
                    'summary' => $summary,
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                ])->load('closedBy');
            });
        } catch (QueryException $exception) {
            if ($this->isClosed($date)) {
                throw new ConflictHttpException('Este dia já está fechado.');
            }

            throw $exception;
        }
    }

    public function reopen(DailyClosure $closure, string $reason, User $admin): DailyClosure
    {
        return DB::transaction(function () use ($closure, $reason, $admin): DailyClosure {
            $lockedClosure = DailyClosure::query()->lockForUpdate()->findOrFail($closure->id);

            if ($lockedClosure->reopened_at !== null || $lockedClosure->active_date === null) {
                throw new ConflictHttpException('Este fecho já foi reaberto.');
            }

            $lockedClosure->update([
                'active_date' => null,
                'reopened_by' => $admin->id,
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ]);

            return $lockedClosure->refresh()->load(['closedBy', 'reopenedBy']);
        });
    }

    public function activeForDate(string|CarbonInterface $date): ?DailyClosure
    {
        $value = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return DailyClosure::with('closedBy')->where('active_date', $value)->first();
    }

    public function isClosed(string|CarbonInterface $date): bool
    {
        $value = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return DailyClosure::where('active_date', $value)->exists();
    }

    public function latestForDate(string $date): ?DailyClosure
    {
        return DailyClosure::with(['closedBy', 'reopenedBy'])
            ->whereDate('date', $date)
            ->latest('closed_at')
            ->first();
    }

    public function ensureOpen(string|CarbonInterface $date): void
    {
        if ($this->isClosed($date)) {
            throw new ConflictHttpException('O dia está fechado e não aceita alterações.');
        }
    }
}
