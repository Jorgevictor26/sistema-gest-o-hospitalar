import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs';

import { AuthService } from '../../../core/auth/auth.service';
import {
  AppointmentStatus,
  DoctorDashboardViewModel,
  DoctorDashboardStatus,
} from './doctor-dashboard.models';
import { DoctorDashboardService } from './doctor-dashboard.service';

@Component({
  selector: 'app-doctor-dashboard',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './doctor-dashboard.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DoctorDashboardComponent {
  private readonly authService = inject(AuthService);
  private readonly dashboardService = inject(DoctorDashboardService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly currencyFormatter = new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    currencyDisplay: 'code',
    maximumFractionDigits: 2,
  });

  protected readonly user = this.authService.currentUser;
  protected readonly status = signal<DoctorDashboardStatus>('loading');
  protected readonly errorTitle = signal('Não foi possível apresentar o dashboard');
  protected readonly errorMessage = signal('Não foi possível carregar o dashboard.');
  protected readonly viewModel = signal<DoctorDashboardViewModel | null>(null);
  protected readonly hasPatientFlow = computed(() => this.viewModel()?.patientFlow.some((point) => point.value > 0) ?? false);
  protected readonly firstName = computed(() => (this.user()?.name ?? '').trim().split(/\s+/)[0] ?? '');
  protected readonly greeting = computed(() => {
    const hour = Number(
      new Intl.DateTimeFormat('pt-AO', { hour: '2-digit', hour12: false, timeZone: 'Africa/Luanda' })
        .formatToParts(new Date())
        .find((part) => part.type === 'hour')?.value ?? 0,
    );
    return hour < 12 ? 'Bom dia' : hour < 18 ? 'Boa tarde' : 'Boa noite';
  });

  constructor() {
    this.load();
  }

  protected retry(): void {
    this.load();
  }

  protected formatKz(value: number | null): string {
    return value === null ? '—' : this.currencyFormatter.format(value).replace('AOA', 'Kz');
  }

  protected statusLabel(status: AppointmentStatus): string {
    return {
      scheduled: 'Agendada',
      confirmed: 'Confirmada',
      completed: 'Realizada',
      no_show: 'Paciente ausente',
      cancelled: 'Cancelada',
    }[status];
  }

  protected statusClasses(status: AppointmentStatus): string {
    return {
      scheduled: 'bg-sky-50 text-sky-700',
      confirmed: 'bg-emerald-50 text-emerald-700',
      completed: 'bg-slate-100 text-slate-700',
      no_show: 'bg-amber-50 text-amber-700',
      cancelled: 'bg-red-50 text-red-700',
    }[status];
  }

  private load(): void {
    this.status.set('loading');
    this.errorTitle.set('Não foi possível apresentar o dashboard');
    this.errorMessage.set('Não foi possível carregar o dashboard.');

    const today = new Intl.DateTimeFormat('en-CA', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date());

    this.dashboardService
      .load(today)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => {
          if (this.status() === 'loading') this.status.set('ready');
        }),
      )
      .subscribe({
        next: (viewModel) => {
          this.viewModel.set(viewModel);
          this.status.set('ready');
        },
        error: (error: unknown) => {
          this.viewModel.set(null);
          if ((error instanceof HttpErrorResponse && error.status === 403)
            || (error instanceof Error && error.message === 'DOCTOR_SCOPE_MISMATCH')) {
            this.errorTitle.set('Falha de autorização');
          }
          this.errorMessage.set(this.httpErrorMessage(error));
          this.status.set('error');
        },
      });
  }

  private httpErrorMessage(error: unknown): string {
    if (error instanceof Error && error.message === 'DOCTOR_SCOPE_MISMATCH') {
      return 'A resposta não está limitada ao perfil médico autenticado e foi bloqueada.';
    }
    if (!(error instanceof HttpErrorResponse)) return 'O backend devolveu uma resposta vazia ou inválida.';
    if (error.status === 0) return 'Não foi possível ligar ao servidor.';
    if (error.status === 401) return 'A sessão expirou. Inicie sessão novamente.';
    if (error.status === 403) return 'Não tem permissão para consultar este dashboard.';
    if (error.status === 404) return 'O perfil médico associado não foi encontrado.';
    return 'Não foi possível carregar o dashboard.';
  }
}
