<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'amount_paid',
        'total_amount',
        'commission_percentage',
        'registered_by',
        'attendance_date',
    ];

    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class, 'attendance_procedure')->withPivot('price');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(AttendanceEdit::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function pendingAmount(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->amount_paid);
    }

    public function paymentStatus(): string
    {
        return match (true) {
            (float) $this->amount_paid <= 0 => 'unpaid',
            (float) $this->amount_paid < (float) $this->total_amount => 'partial',
            default => 'paid',
        };
    }

    public function commissionAmount(): float
    {
        return (float) $this->total_amount * (float) $this->commission_percentage / 100;
    }

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'attendance_date' => 'date',
        ];
    }
}
