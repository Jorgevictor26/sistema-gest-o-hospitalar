import { ChangeDetectionStrategy, Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormControl, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { debounceTime, finalize } from 'rxjs';

import { AuthService } from '../../../core/auth/auth.service';
import { RecentAttendance } from '../admin/admin-dashboard.models';
import {
  AttendanceFilters,
  PaginatedAttendances,
  ReceptionistDashboardData,
} from './receptionist-dashboard.models';
import { ReceptionistDashboardService } from './receptionist-dashboard.service';

@Component({
  selector: 'app-receptionist-dashboard',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './receptionist-dashboard.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ReceptionistDashboardComponent {
  private readonly service = inject(ReceptionistDashboardService);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);
  private readonly dateFormatter = new Intl.DateTimeFormat('pt-AO', {
    dateStyle: 'long',
    timeStyle: 'short',
    timeZone: 'Africa/Luanda',
  });
  private readonly today = this.isoDate(new Date());

  protected readonly user = this.authService.currentUser;
  protected readonly now = signal(new Date());
  protected readonly data = signal<ReceptionistDashboardData | null>(null);
  protected readonly isLoading = signal(true);
  protected readonly tableLoading = signal(false);
  protected readonly closingDay = signal(false);
  protected readonly closeConfirmationOpen = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly feedback = signal<{ type: 'success' | 'error'; message: string } | null>(null);
  protected readonly lastAttendance = signal<RecentAttendance | null>(null);
  protected readonly formattedNow = computed(() => this.dateFormatter.format(this.now()));
  protected readonly greeting = computed(() => {
    const hour = Number(
      new Intl.DateTimeFormat('pt-AO', { hour: '2-digit', hour12: false, timeZone: 'Africa/Luanda' })
        .formatToParts(this.now())
        .find((part) => part.type === 'hour')?.value ?? 0,
    );
    return hour < 12 ? 'Bom dia' : hour < 18 ? 'Boa tarde' : 'Boa noite';
  });
  protected readonly firstName = computed(() => this.user()?.name.split(' ')[0] ?? '');

  protected readonly filtersForm = new FormGroup({
    search: new FormControl('', { nonNullable: true }),
    doctorId: new FormControl('', { nonNullable: true }),
    paymentStatus: new FormControl<AttendanceFilters['paymentStatus']>('', { nonNullable: true }),
  });

  constructor() {
    const clock = window.setInterval(() => this.now.set(new Date()), 60_000);
    this.destroyRef.onDestroy(() => window.clearInterval(clock));

    this.filtersForm.valueChanges
      .pipe(debounceTime(350), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadAttendances(1));

    this.load();
  }

  protected load(): void {
    this.isLoading.set(true);
    this.errorMessage.set(null);

    this.service
      .load(this.today, this.filters(1))
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading.set(false)),
      )
      .subscribe({
        next: (data) => {
          this.data.set(data);
          this.lastAttendance.set(data.attendances.data[0] ?? null);
        },
        error: (error: unknown) => this.errorMessage.set(this.httpErrorMessage(error, 'carregar o dashboard')),
      });
  }

  protected loadAttendances(page: number): void {
    if (!this.data() || this.tableLoading()) return;
    this.tableLoading.set(true);

    this.service
      .getAttendances(this.today, this.filters(page))
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.tableLoading.set(false)),
      )
      .subscribe({
        next: (attendances) => this.data.update((data) => (data ? { ...data, attendances } : data)),
        error: (error: unknown) => this.showFeedback('error', this.httpErrorMessage(error, 'atualizar os atendimentos')),
      });
  }

  protected confirmCloseDay(): void {
    if (this.data()?.closureStatus.is_closed || this.closingDay()) return;
    this.closeConfirmationOpen.set(true);
  }

  protected closeDay(): void {
    this.closeConfirmationOpen.set(false);
    this.closingDay.set(true);

    this.service
      .closeDay(this.today)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.closingDay.set(false)),
      )
      .subscribe({
        next: () => {
          this.showFeedback('success', 'O dia foi fechado com sucesso.');
          this.load();
        },
        error: (error: unknown) => this.showFeedback('error', this.httpErrorMessage(error, 'fechar o dia')),
      });
  }

  protected paymentLabel(status: RecentAttendance['payment_status']): string {
    return { paid: 'Pago', partial: 'Parcial', unpaid: 'Não pago' }[status];
  }

  protected paymentClasses(status: RecentAttendance['payment_status']): string {
    return {
      paid: 'bg-emerald-100 text-emerald-700',
      partial: 'bg-amber-100 text-amber-700',
      unpaid: 'bg-red-100 text-[#ba1a1a]',
    }[status];
  }

  protected procedures(attendance: RecentAttendance): string {
    return attendance.procedures.map((procedure) => procedure.procedure).join(', ');
  }

  protected attendanceTime(attendance: RecentAttendance): string {
    return new Intl.DateTimeFormat('pt-AO', {
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date(attendance.created_at));
  }

  protected patientInitials(name: string): string {
    return name.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase();
  }

  protected pagination(): PaginatedAttendances['meta'] | null {
    return this.data()?.attendances.meta ?? null;
  }

  private filters(page: number): AttendanceFilters {
    const values = this.filtersForm.getRawValue();
    return { ...values, search: values.search.trim(), page };
  }

  private showFeedback(type: 'success' | 'error', message: string): void {
    this.feedback.set({ type, message });
    window.setTimeout(() => this.feedback.set(null), 4_000);
  }

  private httpErrorMessage(error: unknown, operation: string): string {
    if (!(error instanceof HttpErrorResponse)) {
      return `O servidor devolveu uma resposta vazia ou inválida ao ${operation}.`;
    }

    if (error.status === 401) {
      this.authService.logout();
      void this.router.navigateByUrl('/login');
      return 'A sua sessão expirou. Inicie sessão novamente.';
    }

    if (error.status === 403) return 'Não tem permissão para realizar esta operação.';
    if (error.status === 404) return 'O recurso solicitado não foi encontrado.';
    if (error.status === 409) return this.backendMessage(error) ?? 'O dia já está fechado ou existe um conflito.';
    if (error.status === 422) return this.backendValidationMessage(error) ?? 'Os dados enviados não são válidos.';
    if (error.status === 0) return 'Não foi possível ligar ao servidor. Verifique a ligação e tente novamente.';

    return `Não foi possível ${operation}. Tente novamente.`;
  }

  private backendMessage(error: HttpErrorResponse): string | null {
    if (typeof error.error !== 'object' || error.error === null) return null;
    const message = (error.error as Record<string, unknown>)['message'];
    return typeof message === 'string' ? message : null;
  }

  private backendValidationMessage(error: HttpErrorResponse): string | null {
    if (typeof error.error !== 'object' || error.error === null) return null;
    const errors = (error.error as Record<string, unknown>)['errors'];
    if (typeof errors !== 'object' || errors === null) return this.backendMessage(error);

    for (const messages of Object.values(errors as Record<string, unknown>)) {
      if (Array.isArray(messages) && typeof messages[0] === 'string') return messages[0];
    }

    return this.backendMessage(error);
  }

  private isoDate(date: Date): string {
    return new Intl.DateTimeFormat('en-CA', {
      year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Africa/Luanda',
    }).format(date);
  }
}
