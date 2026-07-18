<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a receptionist can register an appointment', function () {
    $receptionist = User::factory()->create();
    $receptionist->roles()->attach(Role::create(['name' => 'receptionist']));
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'speciality' => 'Cardiologia',
    ]);
    $patient = Patient::create([
        'name' => 'Maria Silva',
        'phone_number' => '923000000',
        'gender' => 'female',
        'identity_card' => '000000001LA001',
    ]);

    Sanctum::actingAs($receptionist);

    $response = $this->postJson('/api/appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->seconds(0)->toDateTimeString(),
        'duration_minutes' => 30,
        'reason' => 'Consulta de rotina',
        'notes' => 'Primeira consulta',
        'status' => 'completed',
        'scheduled_by' => User::factory()->create()->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.patient.id', $patient->id)
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.scheduled_by.id', $receptionist->id)
        ->assertJsonPath('data.duration_minutes', 30)
        ->assertJsonPath('data.status', 'scheduled');

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_by' => $receptionist->id,
        'duration_minutes' => 30,
        'status' => 'scheduled',
        'reason' => 'Consulta de rotina',
    ]);
});

test('appointment registration validates required data', function () {
    $receptionist = User::factory()->create();
    $receptionist->roles()->attach(Role::create(['name' => 'receptionist']));

    Sanctum::actingAs($receptionist);

    $this->postJson('/api/appointments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'patient_id',
            'doctor_id',
            'scheduled_at',
            'duration_minutes',
        ]);

    $this->assertDatabaseCount('appointments', 0);
});

test('a receptionist can reschedule an appointment without conflicting with itself', function () {
    $receptionist = User::factory()->create();
    $receptionist->roles()->attach(Role::create(['name' => 'receptionist']));
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'speciality' => 'Cardiologia',
        'is_available' => true,
    ]);
    $patient = Patient::create([
        'name' => 'Ana Manuel',
        'phone_number' => '924000000',
        'gender' => 'female',
        'identity_card' => '000000002LA002',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_by' => $receptionist->id,
        'scheduled_at' => now()->addDay(),
        'duration_minutes' => 30,
        'status' => Appointment::STATUS_SCHEDULED,
    ]);
    $newDate = now()->addDays(2)->seconds(0);

    Sanctum::actingAs($receptionist);

    $this->patchJson("/api/appointments/{$appointment->id}/reschedule", [
        'scheduled_at' => $newDate->toDateTimeString(),
        'duration_minutes' => 45,
        'notes' => 'Novo horário',
    ])->assertOk()
        ->assertJsonPath('data.duration_minutes', 45)
        ->assertJsonPath('data.status', Appointment::STATUS_SCHEDULED);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'duration_minutes' => 45,
        'notes' => 'Novo horário',
    ]);
});

test('rescheduling rejects an overlapping doctor appointment', function () {
    $receptionist = User::factory()->create();
    $receptionist->roles()->attach(Role::create(['name' => 'receptionist']));
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'speciality' => 'Cardiologia',
        'is_available' => true,
    ]);
    $patient = Patient::create([
        'name' => 'Pedro Neto',
        'phone_number' => '925000000',
        'gender' => 'male',
        'identity_card' => '000000003LA003',
    ]);
    $startsAt = now()->addDays(2)->seconds(0);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_by' => $receptionist->id,
        'scheduled_at' => now()->addDay(),
        'duration_minutes' => 30,
        'status' => Appointment::STATUS_CONFIRMED,
    ]);
    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_by' => $receptionist->id,
        'scheduled_at' => $startsAt,
        'duration_minutes' => 60,
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    Sanctum::actingAs($receptionist);

    $this->patchJson("/api/appointments/{$appointment->id}/reschedule", [
        'scheduled_at' => $startsAt->copy()->addMinutes(30)->toDateTimeString(),
        'duration_minutes' => 30,
    ])->assertConflict()
        ->assertJsonPath('message', 'O médico já possui uma marcação neste horário.');
});

test('terminal appointment states reject further transitions', function () {
    $receptionist = User::factory()->create();
    $receptionist->roles()->attach(Role::create(['name' => 'receptionist']));
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'speciality' => 'Cardiologia',
    ]);
    $patient = Patient::create([
        'name' => 'Joana Costa',
        'phone_number' => '926000000',
        'gender' => 'female',
        'identity_card' => '000000004LA004',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_by' => $receptionist->id,
        'scheduled_at' => now()->addDay(),
        'duration_minutes' => 30,
        'status' => Appointment::STATUS_SCHEDULED,
    ]);

    Sanctum::actingAs($receptionist);

    $this->patchJson("/api/appointments/{$appointment->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', Appointment::STATUS_COMPLETED);

    $this->patchJson("/api/appointments/{$appointment->id}/confirm")
        ->assertConflict()
        ->assertJsonPath('message', 'Transição de estado inválida para esta marcação.');
});
