<?php

namespace App\Http\Controllers;

use App\DTOs\DoctorDTO;
use Illuminate\Http\Request;
use App\Http\Requests\DoctorRequest;
use App\Repositories\DoctorService;

class DoctorController extends Controller
{

    public function __construct(
        private readonly DoctorService $doctor_service,
    ) {}
    
    public function registerDoctor(DoctorRequest $request)
    {
        $doctor = $this->doctor_service->create(DoctorDTO::fromArray($request->validated()));

        return response()->json(
            $doctor
        );
    }
}
