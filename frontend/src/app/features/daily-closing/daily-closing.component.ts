import { ChangeDetectionStrategy, Component, HostListener, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';
import { createDailyClosingViewModel } from './daily-closing.viewmodel';

@Component({
  selector: 'app-daily-closing',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './daily-closing.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DailyClosingComponent {
  private readonly authService = inject(AuthService);
  private readonly moneyFormatter = new Intl.NumberFormat('pt-AO', {
    maximumFractionDigits: 0,
  });
  private readonly dateFormatter = new Intl.DateTimeFormat('pt-AO', {
    dateStyle: 'long', timeZone: 'Africa/Luanda',
  });

  protected readonly user = this.authService.currentUser;
  protected readonly viewModel = signal(createDailyClosingViewModel(
    this.today(),
    this.user()?.roles.includes('admin') ?? false,
  ));
  protected readonly search = signal('');
  protected readonly attendancePage = signal(1);
  protected readonly attendancePageSize = 2;
  protected readonly actionNotice = signal<string | null>(null);
  protected readonly closingDay = signal(false);
  protected readonly reopeningDay = signal(false);
  protected readonly exportingPdf = signal(false);
  protected readonly exportingCsv = signal(false);
  protected readonly printing = signal(false);
  protected readonly closeConfirmationOpen = signal(false);
  protected readonly closeValuesReviewed = signal(false);
  protected readonly reopenConfirmationOpen = signal(false);
  protected readonly reopenReason = signal('');
  protected readonly reopenAttempted = signal(false);
  protected readonly isAdmin = computed(() => this.user()?.roles.includes('admin') ?? false);
  protected readonly summaryCards = computed(() => [
    { label: 'Total de pacientes', value: this.viewModel().summary.totalPatients, money: false, icon: 'groups' },
    { label: 'Total de atendimentos', value: this.viewModel().summary.totalAttendances, money: false, icon: 'clinical_notes' },
    { label: 'Total faturado', value: this.viewModel().summary.totalBilled, money: true, icon: 'receipt_long' },
    { label: 'Total recebido', value: this.viewModel().summary.totalReceived, money: true, icon: 'payments' },
    { label: 'Total pendente', value: this.viewModel().summary.totalPending, money: true, icon: 'pending_actions' },
  ] as const);
  protected readonly filteredAttendances = computed(() => {
    const term = this.search().trim().toLocaleLowerCase('pt');
    if (!term) return this.viewModel().attendances;
    return this.viewModel().attendances.filter((attendance) =>
      `${attendance.code} ${attendance.patient} ${attendance.doctor} ${attendance.procedures.join(' ')}`
        .toLocaleLowerCase('pt').includes(term),
    );
  });
  protected readonly attendanceTotalPages = computed(() =>
    Math.max(1, Math.ceil(this.filteredAttendances().length / this.attendancePageSize)),
  );
  protected readonly paginatedAttendances = computed(() => {
    const start = (this.attendancePage() - 1) * this.attendancePageSize;
    return this.filteredAttendances().slice(start, start + this.attendancePageSize);
  });

  protected updateSearch(event: Event): void {
    this.search.set((event.target as HTMLInputElement).value);
    this.attendancePage.set(1);
  }

  @HostListener('document:keydown.escape')
  protected closeActiveModal(): void {
    if (this.closeConfirmationOpen()) this.cancelCloseConfirmation();
    if (this.reopenConfirmationOpen()) this.cancelReopenConfirmation();
  }

  protected changeAttendancePage(page: number): void {
    if (page < 1 || page > this.attendanceTotalPages()) return;
    this.attendancePage.set(page);
  }

  protected updateDate(event: Event): void {
    const date = (event.target as HTMLInputElement).value;
    if (!date) return;
    this.viewModel.update((viewModel) => ({ ...viewModel, selectedDate: date }));
    this.actionNotice.set(null);
  }

  protected changeDay(days: number): void {
    const date = new Date(`${this.viewModel().selectedDate}T12:00:00`);
    date.setDate(date.getDate() + days);
    this.viewModel.update((viewModel) => ({ ...viewModel, selectedDate: this.isoDate(date) }));
    this.actionNotice.set(null);
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

  protected formatDate(value: string): string {
    return this.dateFormatter.format(new Date(`${value}T12:00:00`));
  }

  protected formatShortDate(value: string): string {
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Africa/Luanda',
    }).format(new Date(`${value}T12:00:00`));
  }

  protected formatClosedAt(): string {
    if (!this.viewModel().closure.closedAt) return '—';
    const value = new Date(`${this.viewModel().selectedDate}T${this.viewModel().closure.closedAt}:00`);
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(value);
  }

  protected formatAuditDate(value: string): string {
    return new Intl.DateTimeFormat('pt-AO', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(new Date(value));
  }

  protected initials(name: string): string {
    return name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
  }

  protected paymentStatusLabel(status: 'paid' | 'partial' | 'unpaid'): string {
    return { paid: 'Pagos', partial: 'Parciais', unpaid: 'Não pagos' }[status];
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
      year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Africa/Luanda',
    }).format(date);
  }
}
