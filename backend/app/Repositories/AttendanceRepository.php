<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository
{
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }
}
