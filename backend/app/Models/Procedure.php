<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    protected $fillable = [
        'name',
        'price'
    ];
}
