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
        'attendance_date'
    ];

    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class, 'attendance_procedure')->withPivot('price');
    }

    public function patient():BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
