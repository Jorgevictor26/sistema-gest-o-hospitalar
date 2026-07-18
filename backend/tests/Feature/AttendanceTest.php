<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an authenticated user can register an attendance', function () {
    $registrar = User::factory()->create();
    $doctorUser = User::factory()->create();
    $doctor = Doctor::create([
        'user_id' => $doctorUser->id,
        'speciality' => 'Cardiologia',
    ]);
    $patient = Patient::create([
        'name' => 'Maria Silva',
        'phone_number' => '923000000',
        'gender' => 'female',
        'identity_card' => '000000001LA001',
    ]);
    $consultation = Procedure::create([
        'procedure' => 'Consulta',
        'price' => '10000.00',
    ]);
    $exam = Procedure::create([
        'procedure' => 'Exame',
        'price' => '2500.50',
    ]);

    Sanctum::actingAs($registrar);

    $response = $this->postJson('/api/attendances', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'amount_paid' => '5000.00',
        'attendance_date' => '2026-07-17',
        'procedures' => [$consultation->id, $exam->id],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('patient_id', $patient->id)
        ->assertJsonPath('doctor_id', $doctor->id)
        ->assertJsonPath('registered_by', $registrar->id)
        ->assertJsonPath('amount_paid', '5000.00')
        ->assertJsonPath('total_amount', '12500.50')
        ->assertJsonCount(2, 'procedures');

    $this->assertDatabaseHas('attendances', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'registered_by' => $registrar->id,
        'total_amount' => '12500.50',
    ]);

    $this->assertDatabaseHas('attendance_procedure', [
        'attendance_id' => $response->json('id'),
        'procedure_id' => $consultation->id,
        'price' => '10000.00',
    ]);
});

test('an attendance cannot receive more than its total', function () {
    $registrar = User::factory()->create();
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'speciality' => 'Clínica geral',
    ]);
    $patient = Patient::create([
        'name' => 'José Manuel',
        'phone_number' => '924000000',
        'gender' => 'male',
        'identity_card' => '000000002LA002',
    ]);
    $procedure = Procedure::create([
        'procedure' => 'Consulta',
        'price' => '1000.00',
    ]);

    Sanctum::actingAs($registrar);

    $this->postJson('/api/attendances', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'amount_paid' => '1000.01',
        'attendance_date' => '2026-07-17',
        'procedures' => [$procedure->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('amount_paid');

    $this->assertDatabaseCount('attendances', 0);
});
