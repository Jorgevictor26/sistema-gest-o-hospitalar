<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Attendance extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'amount_paid',
        'total_amount',
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

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'attendance_date' => 'date',
        ];
    }
}
