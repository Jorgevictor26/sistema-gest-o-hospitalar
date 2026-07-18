<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosure extends Model
{
    protected $fillable = [
        'date', 'active_date', 'summary', 'closed_by', 'closed_at',
        'reopened_by', 'reopened_at', 'reopen_reason',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'summary' => 'array',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }
}
