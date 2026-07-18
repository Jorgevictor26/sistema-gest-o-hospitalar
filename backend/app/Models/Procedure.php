<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procedure extends Model
{
    protected $fillable = [
        'procedure',
        'price',
    ];

    public function attendances(): BelongsToMany
    {
        return $this->belongsToMany(Attendance::class, 'attendance_procedure')->withPivot('price');
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
