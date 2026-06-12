<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    protected function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class . 'registered_by');
    }
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => 'string',
            'is_active' => 'boolean'
        ];
    }
}
