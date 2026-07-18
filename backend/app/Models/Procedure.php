<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procedure extends Model
{
    protected $fillable = [
        'procedure',
        'description',
        'price',
        'is_active',
    ];

    public function attendances(): BelongsToMany
    {
        return $this->belongsToMany(Attendance::class, 'attendance_procedure')
            ->withTrashed()
            ->withPivot('price');
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
