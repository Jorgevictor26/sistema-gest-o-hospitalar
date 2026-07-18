import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormControl, FormGroup, ReactiveFormsModule, ValidatorFn, Validators } from '@angular/forms';
import { Router } from '@angular/router';

import {
  ATTENDANCE_FORM_VIEW_MODEL,
  DoctorOption,
  PaymentPreview,
  PatientOption,
  ProcedureOption,
} from './create-attendance.viewmodel';

type PaymentMethod = '' | 'cash' | 'bank_transfer' | 'card' | 'insurance' | 'other';

const paymentMethodRequired: ValidatorFn = (control) => {
  const paid = Number(control.get('amount_paid')?.value ?? 0);
  const method = control.get('payment_method')?.value;
  return paid > 0 && !method ? { paymentMethodRequired: true } : null;
};

@Component({
  selector: 'app-create-attendance',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './create-attendance.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CreateAttendanceComponent {
  private readonly router = inject(Router);
  private readonly currencyFormatter = new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    currencyDisplay: 'code',
    minimumFractionDigits: 2,
  });

  protected readonly patients = signal<readonly PatientOption[]>(ATTENDANCE_FORM_VIEW_MODEL.patients);
  protected readonly doctors = signal<readonly DoctorOption[]>(ATTENDANCE_FORM_VIEW_MODEL.doctors.filter((doctor) => doctor.isActive));
  protected readonly procedures = signal<readonly ProcedureOption[]>(ATTENDANCE_FORM_VIEW_MODEL.procedures.filter((procedure) => procedure.isActive));
  protected readonly patientSearch = signal('');
  protected readonly patientSearchFocused = signal(false);
  protected readonly doctorSearch = signal('');
  protected readonly doctorSearchFocused = signal(false);
  protected readonly procedureSearch = signal('');
  protected readonly submittedWithoutApi = signal(false);
  protected readonly submitAttempted = signal(false);
  protected readonly isSaving = signal(false);
  protected readonly patientsLoading = signal(false);
  protected readonly doctorsLoading = signal(false);
  protected readonly proceduresLoading = signal(false);
  protected readonly loadError = signal<string | null>(null);
  protected readonly dayClosed = signal(false);

  protected readonly form = new FormGroup(
    {
      patient_id: new FormControl<number | null>(null, Validators.required),
      doctor_id: new FormControl<number | null>(null, Validators.required),
      procedure_ids: new FormControl<number[]>([], { nonNullable: true, validators: [Validators.required] }),
      amount_paid: new FormControl(0, { nonNullable: true, validators: [Validators.required, Validators.min(0)] }),
      attendance_date: new FormControl(this.today(), { nonNullable: true, validators: Validators.required }),
      payment_method: new FormControl<PaymentMethod>('', { nonNullable: true }),
    },
    { validators: paymentMethodRequired },
  );
  private readonly formValue = toSignal(this.form.valueChanges, { initialValue: this.form.getRawValue() });

  protected readonly selectedPatient = computed(() =>
    this.patients().find((patient) => patient.id === this.formValue().patient_id) ?? null,
  );
  protected readonly selectedDoctor = computed(() =>
    this.doctors().find((doctor) => doctor.id === this.formValue().doctor_id) ?? null,
  );
  protected readonly selectedProcedures = computed(() => {
    const ids = this.formValue().procedure_ids ?? [];
    return this.procedures().filter((procedure) => ids.includes(procedure.id));
  });
  // Previsões exclusivas da interface. O backend continua responsável pelos valores oficiais.
  protected readonly totalPreview = computed(() =>
    this.selectedProcedures().reduce((sum, procedure) => sum + procedure.price, 0),
  );
  protected readonly pendingPreview = computed(() =>
    Math.max(this.totalPreview() - (this.formValue().amount_paid ?? 0), 0),
  );
  protected readonly filteredPatients = computed(() => {
    const term = this.normalize(this.patientSearch());
    if (!term) return this.patients();
    return this.patients().filter((patient) =>
      this.normalize(`${patient.name} ${patient.identityCard} ${patient.phoneNumber}`).includes(term),
    );
  });
  protected readonly filteredDoctors = computed(() => {
    const term = this.normalize(this.doctorSearch());
    if (!term) return this.doctors();
    return this.doctors().filter((doctor) => this.normalize(`${doctor.name} ${doctor.speciality}`).includes(term));
  });
  protected readonly filteredProcedures = computed(() => {
    const term = this.normalize(this.procedureSearch());
    if (!term) return this.procedures();
    return this.procedures().filter((procedure) => this.normalize(procedure.name).includes(term));
  });
  protected readonly paymentPreview = computed<PaymentPreview>(() => {
    const total = this.totalPreview();
    const paid = this.formValue().amount_paid ?? 0;
    const status = paid <= 0 ? 'unpaid' : paid < total ? 'partial' : 'paid';
    return { totalPreview: total, pendingPreview: this.pendingPreview(), status };
  });

  constructor() {
    effect(() => this.dayClosed() ? this.form.disable() : this.form.enable());
  }

  protected updatePatientSearch(event: Event): void {
    this.patientSearch.set((event.target as HTMLInputElement).value);
  }

  protected updateProcedureSearch(event: Event): void {
    this.procedureSearch.set((event.target as HTMLInputElement).value);
  }

  protected updateDoctorSearch(event: Event): void {
    this.doctorSearch.set((event.target as HTMLInputElement).value);
  }

  protected selectPatient(patient: PatientOption): void {
    this.form.controls.patient_id.setValue(patient.id);
    this.form.controls.patient_id.markAsTouched();
    this.form.controls.patient_id.markAsDirty();
    this.patientSearch.set(patient.name);
    this.patientSearchFocused.set(false);
  }

  protected removePatient(): void {
    this.form.controls.patient_id.setValue(null);
    this.form.controls.patient_id.markAsDirty();
    this.patientSearch.set('');
  }

  protected selectDoctor(doctor: DoctorOption): void {
    this.form.controls.doctor_id.setValue(doctor.id);
    this.form.controls.doctor_id.markAsTouched();
    this.form.controls.doctor_id.markAsDirty();
    this.doctorSearch.set(doctor.name);
    this.doctorSearchFocused.set(false);
  }

  protected updateProcedureSelection(procedureId: number, event: Event): void {
    this.selectProcedure(procedureId, (event.target as HTMLInputElement).checked);
  }

  protected selectProcedure(procedureId: number, selected: boolean): void {
    const ids = this.form.controls.procedure_ids.value;
    this.form.controls.procedure_ids.setValue(
      selected && !ids.includes(procedureId) ? [...ids, procedureId] : ids.filter((id) => id !== procedureId),
    );
    this.form.controls.procedure_ids.markAsTouched();
    this.form.controls.procedure_ids.markAsDirty();
  }

  protected removeProcedure(procedureId: number): void {
    this.selectProcedure(procedureId, false);
  }

  protected submit(): void {
    this.submittedWithoutApi.set(false);
    this.submitAttempted.set(true);
    this.form.markAllAsTouched();
    this.form.updateValueAndValidity();
    if (this.form.invalid || this.dayClosed() || this.isSaving()) return;

    this.submittedWithoutApi.set(true);
  }

  protected cancel(): void {
    if (this.form.dirty && !window.confirm('Existem alterações por guardar. Deseja mesmo sair?')) return;
    void this.router.navigateByUrl('/attendances');
  }

  protected showError(control: { touched: boolean; invalid: boolean }): boolean {
    return control.invalid && (control.touched || this.submitAttempted());
  }

  protected formatKz(value: number): string {
    return this.currencyFormatter.format(value).replace('AOA', 'Kz');
  }

  protected initials(name: string): string {
    return name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
  }

  protected patientAge(dateOfBirth: string): number {
    const birth = new Date(`${dateOfBirth}T12:00:00`);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) age--;
    return age;
  }

  protected formatDate(date: string): string {
    return new Intl.DateTimeFormat('pt-AO', { dateStyle: 'medium', timeZone: 'Africa/Luanda' })
      .format(new Date(`${date}T12:00:00`));
  }

  protected paymentLabel(): string {
    return { paid: 'Pago', partial: 'Parcial', unpaid: 'Não pago' }[this.paymentPreview().status];
  }

  protected paymentClasses(): string {
    return {
      paid: 'border-emerald-300 bg-emerald-50 text-emerald-700',
      partial: 'border-amber-300 bg-amber-50 text-amber-700',
      unpaid: 'border-red-200 bg-red-50 text-red-700',
    }[this.paymentPreview().status];
  }

  private today(): string {
    return new Intl.DateTimeFormat('en-CA', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date());
  }

  private normalize(value: string): string {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }
}
