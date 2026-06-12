<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name',
        'phone_number',
        'gender',
        'date_of_birth',
        'identity_card',
        'email',
        'address'
    ];
    
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
