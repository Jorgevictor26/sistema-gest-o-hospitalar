<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_NO_SHOW = 'no_show';

    public const RESCHEDULABLE_STATUSES = [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED];

    public const STATUS_TRANSITIONS = [
        self::STATUS_SCHEDULED => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_NO_SHOW],
        self::STATUS_CONFIRMED => [self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_NO_SHOW],
        self::STATUS_CANCELLED => [],
        self::STATUS_COMPLETED => [],
        self::STATUS_NO_SHOW => [],
    ];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'scheduled_by',
        'scheduled_at',
        'duration_minutes',
        'reason',
        'notes',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'attendance_id',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }
}
