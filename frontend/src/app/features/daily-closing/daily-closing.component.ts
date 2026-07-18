import { HttpErrorResponse } from '@angular/common/http';
import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  HostListener,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Subject, debounceTime, distinctUntilChanged } from 'rxjs';

import { AuthService } from '../../core/auth/auth.service';
import { mapAttendancesToViewModel, mapDailyClosingToViewModel } from './daily-closing.mapper';
import { DailyClosingService } from './daily-closing.service';
import { ClosingAttendance, createDailyClosingViewModel } from './daily-closing.viewmodel';

@Component({
  selector: 'app-daily-closing',
  standalone: true,
  imports: [],
  templateUrl: './daily-closing.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DailyClosingComponent {
  private readonly authService = inject(AuthService);
  private readonly dailyClosingService = inject(DailyClosingService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly searchChanges = new Subject<string>();
  private readonly moneyFormatter = new Intl.NumberFormat('pt-AO', {
    maximumFractionDigits: 0,
  });
  private readonly dateFormatter = new Intl.DateTimeFormat('pt-AO', {
    dateStyle: 'long',
    timeZone: 'Africa/Luanda',
  });

  protected readonly user = this.authService.currentUser;
  protected readonly currentDate = this.today();
  protected readonly viewModel = signal(
    createDailyClosingViewModel(
      this.currentDate,
      this.user()?.roles.includes('admin') ?? false,
      true,
    ),
  );
  protected readonly search = signal('');
  protected readonly paymentStatusFilter = signal<'' | 'paid' | 'partial' | 'unpaid'>('');
  protected readonly summaryView = signal<'general' | 'doctor' | 'procedure'>('general');
  protected readonly selectedDoctorId = signal<number | null>(null);
  protected readonly selectedAttendance = signal<ClosingAttendance | null>(null);
  protected readonly attendancePage = signal(1);
  protected readonly attendancePageSize = 15;
  protected readonly actionNotice = signal<string | null>(null);
  protected readonly closingDay = signal(false);
  protected readonly reopeningDay = signal(false);
  protected readonly exportingPdf = signal(false);
  protected readonly exportingCsv = signal(false);
  protected readonly closeConfirmationOpen = signal(false);
  protected readonly closeValuesReviewed = signal(false);
  protected readonly reopenConfirmationOpen = signal(false);
  protected readonly reopenReason = signal('');
  protected readonly reopenAttempted = signal(false);
  protected readonly isAdmin = computed(() => this.user()?.roles.includes('admin') ?? false);
  protected readonly summaryCards = computed(
    () =>
      [
        {
          label: 'Total de pacientes atendidos',
          value: this.viewModel().summary.totalPatients,
          money: false,
          icon: 'groups',
        },
        {
          label: 'Total de atendimentos',
          value: this.viewModel().summary.totalAttendances,
          money: false,
          icon: 'clinical_notes',
        },
        {
          label: 'Total de marcações',
          value: null,
          money: false,
          icon: 'event',
        },
        {
          label: 'Total faturado',
          value: this.viewModel().summary.totalBilled,
          money: true,
          icon: 'receipt_long',
        },
        {
          label: 'Total recebido',
          value: this.viewModel().summary.totalReceived,
          money: true,
          icon: 'payments',
        },
        {
          label: 'Total pendente',
          value: this.viewModel().summary.totalPending,
          money: true,
          icon: 'pending_actions',
        },
      ] as const,
  );
  protected readonly filteredAttendances = computed(() => this.viewModel().attendances);
  protected readonly attendanceTotalPages = computed(
    () => this.viewModel().attendancePagination.lastPage,
  );
  protected readonly paginatedAttendances = computed(() => this.filteredAttendances());
  protected readonly selectedDoctor = computed(() => {
    const id = this.selectedDoctorId();
    return id === null
      ? null
      : (this.viewModel().doctors.find((doctor) => doctor.id === id) ?? null);
  });
  protected readonly selectedDoctorAttendances = computed(() => {
    const doctor = this.selectedDoctor();
    if (!doctor) return [];
    return this.viewModel().attendances.filter(
      (attendance) =>
        attendance.doctor.trim().toLocaleLowerCase() === doctor.doctor.trim().toLocaleLowerCase(),
    );
  });

  constructor() {
    this.searchChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadAttendances(this.currentDate, 1));
    this.loadDailyClosing(this.currentDate);
  }

  protected updateSearch(event: Event): void {
    const search = (event.target as HTMLInputElement).value;
    this.search.set(search);
    this.attendancePage.set(1);
    this.searchChanges.next(search.trim());
  }

  protected updatePaymentStatus(event: Event): void {
    this.paymentStatusFilter.set(
      (event.target as HTMLSelectElement).value as '' | 'paid' | 'partial' | 'unpaid',
    );
    this.loadAttendances(this.currentDate, 1);
  }

  @HostListener('document:keydown.escape')
  protected closeActiveModal(): void {
    if (this.selectedAttendance()) this.selectedAttendance.set(null);
    if (this.closeConfirmationOpen()) this.cancelCloseConfirmation();
    if (this.reopenConfirmationOpen()) this.cancelReopenConfirmation();
  }

  protected changeAttendancePage(page: number): void {
    if (page < 1 || page > this.attendanceTotalPages()) return;
    this.loadAttendances(this.currentDate, page);
  }

  protected tabClasses(active: boolean): string {
    return active
      ? 'border-[#005d90] bg-[#005d90] text-white shadow-sm'
      : 'border-[#d4d9dc] bg-white text-[#404850] hover:border-[#8a9499] hover:bg-[#f3f4f5]';
  }

  protected retry(): void {
    this.loadDailyClosing(this.currentDate);
  }

  protected retryAttendances(): void {
    this.loadAttendances(this.currentDate, this.attendancePage());
  }

  protected openAttendance(attendance: ClosingAttendance): void {
    this.selectedAttendance.set(attendance);
  }

  protected selectDoctor(doctorId: number): void {
    this.selectedDoctorId.set(this.selectedDoctorId() === doctorId ? null : doctorId);
  }

  protected firstProcedure(attendance: ClosingAttendance): string {
    return attendance.procedures[0] ?? 'Sem procedimento';
  }

  protected otherProceduresCount(attendance: ClosingAttendance): number {
    return Math.max(attendance.procedures.length - 1, 0);
  }

  protected formatAttendanceTime(value: string): string {
    return new Intl.DateTimeFormat('pt-AO', {
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date(value));
  }

  protected formatAttendanceDate(value: string): string {
    return this.formatShortDate(value);
  }

  protected showUnavailable(action: string): void {
    this.actionNotice.set(`${action} estará disponível na fase de integração com o backend.`);
  }

  protected openCloseConfirmation(): void {
    this.closeValuesReviewed.set(false);
    this.closeConfirmationOpen.set(true);
  }

  protected cancelCloseConfirmation(): void {
    this.closeConfirmationOpen.set(false);
    this.closeValuesReviewed.set(false);
  }

  protected updateCloseReview(event: Event): void {
    this.closeValuesReviewed.set((event.target as HTMLInputElement).checked);
  }

  protected confirmClose(): void {
    if (!this.closeValuesReviewed() || this.closingDay()) return;
    this.cancelCloseConfirmation();
    this.showUnavailable('O fecho do dia');
  }

  protected openReopenConfirmation(): void {
    if (!this.isAdmin()) return;
    this.reopenReason.set('');
    this.reopenAttempted.set(false);
    this.reopenConfirmationOpen.set(true);
  }

  protected cancelReopenConfirmation(): void {
    this.reopenConfirmationOpen.set(false);
    this.reopenReason.set('');
    this.reopenAttempted.set(false);
  }

  protected updateReopenReason(event: Event): void {
    this.reopenReason.set((event.target as HTMLTextAreaElement).value);
  }

  protected confirmReopen(): void {
    this.reopenAttempted.set(true);
    if (!this.isAdmin() || !this.reopenReason().trim() || this.reopeningDay()) return;
    this.cancelReopenConfirmation();
    this.showUnavailable('A reabertura do dia');
  }

  protected formatMoney(value: number): string {
    return `${this.moneyFormatter.format(value)} Kz`;
  }

  protected formatSummaryValue(value: number | null, money: boolean): string | number {
    if (value === null) return '—';
    return money ? this.formatMoney(value) : value;
  }

  protected formatDate(value: string): string {
    return this.dateFormatter.format(new Date(`${value}T12:00:00`));
  }

  protected formatShortDate(value: string): string {
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      timeZone: 'Africa/Luanda',
    }).format(new Date(`${value}T12:00:00`));
  }

  protected formatClosedAt(): string {
    const closedAt = this.viewModel().closure.closedAt;
    if (!closedAt) return '—';
    const value = new Date(closedAt);
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(value);
  }

  protected formatAuditDate(value: string): string {
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date(value));
  }

  protected initials(name: string): string {
    return name
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part[0])
      .join('')
      .toUpperCase();
  }

  protected paymentStatusLabel(status: 'paid' | 'partial' | 'unpaid'): string {
    return { paid: 'Pago', partial: 'Parcial', unpaid: 'Não pago' }[status];
  }

  protected paymentStatusClasses(status: 'paid' | 'partial' | 'unpaid'): string {
    return {
      paid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
      partial: 'border-amber-200 bg-amber-50 text-amber-700',
      unpaid: 'border-red-200 bg-red-50 text-red-700',
    }[status];
  }

  protected attendancePaymentLabel(status: 'paid' | 'partial' | 'unpaid'): string {
    return { paid: 'Pago', partial: 'Parcial', unpaid: 'Não pago' }[status];
  }

  private today(): string {
    return this.isoDate(new Date());
  }

  private isoDate(date: Date): string {
    return new Intl.DateTimeFormat('en-CA', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(date);
  }

  private loadDailyClosing(date: string): void {
    this.actionNotice.set(null);
    this.search.set('');
    this.attendancePage.set(1);
    this.viewModel.set(createDailyClosingViewModel(date, this.isAdmin(), true));

    this.dailyClosingService
      .getByDate(date)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          try {
            this.viewModel.set(mapDailyClosingToViewModel(response, this.isAdmin()));
            this.loadAttendances(date, 1);
          } catch (error: unknown) {
            this.setLoadError(date, error);
          }
        },
        error: (error: unknown) => {
          this.setLoadError(date, error);
        },
      });
  }

  private setLoadError(date: string, error: unknown): void {
    const viewModel = createDailyClosingViewModel(date, this.isAdmin());
    this.viewModel.set({
      ...viewModel,
      errors: { ...viewModel.errors, page: this.errorMessage(error) },
    });
  }

  private loadAttendances(date: string, page: number): void {
    this.attendancePage.set(page);
    this.viewModel.update((viewModel) => ({
      ...viewModel,
      loading: { ...viewModel.loading, attendances: true },
      errors: { ...viewModel.errors, attendances: null },
    }));

    this.dailyClosingService
      .getAttendancesByDate(
        date,
        page,
        this.attendancePageSize,
        this.search().trim() || undefined,
        this.paymentStatusFilter() || undefined,
      )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          try {
            const mapped = mapAttendancesToViewModel(response);
            if (this.viewModel().selectedDate !== date) return;
            this.attendancePage.set(mapped.pagination.currentPage);
            this.viewModel.update((viewModel) => ({
              ...viewModel,
              attendances: mapped.attendances,
              attendancePagination: mapped.pagination,
              loading: { ...viewModel.loading, attendances: false },
            }));
          } catch {
            this.setAttendanceError(
              date,
              'A resposta dos atendimentos possui um formato inesperado.',
            );
          }
        },
        error: (error: unknown) => this.setAttendanceError(date, this.errorMessage(error)),
      });
  }

  private setAttendanceError(date: string, message: string): void {
    if (this.viewModel().selectedDate !== date) return;
    this.viewModel.update((viewModel) => ({
      ...viewModel,
      attendances: [],
      loading: { ...viewModel.loading, attendances: false },
      errors: { ...viewModel.errors, attendances: message },
    }));
  }

  private errorMessage(error: unknown): string {
    if (!(error instanceof HttpErrorResponse)) {
      return 'A resposta do servidor está vazia ou possui um formato inesperado.';
    }

    if (error.status === 0)
      return 'Não foi possível ligar ao servidor. Verifique a sua ligação e tente novamente.';
    if (error.status === 401) return 'A sua sessão expirou. Inicie sessão novamente.';
    if (error.status === 403) return 'Acesso negado: não tem permissão para consultar estes dados.';
    if (error.status === 404)
      return 'O fecho ou recurso solicitado não foi encontrado. Esta resposta não indica automaticamente que o dia está aberto.';
    if (error.status === 409) return 'O estado atual do dia está em conflito com esta consulta.';
    if (error.status === 422) return 'A data selecionada não é válida.';
    if (error.status >= 500)
      return 'O servidor encontrou um erro interno. Tente novamente mais tarde.';

    return 'Não foi possível carregar o fecho diário. Tente novamente.';
  }
}
