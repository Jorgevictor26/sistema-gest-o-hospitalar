import { ChangeDetectionStrategy, Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs';

import { AdminDashboardViewData, RecentAttendance } from './admin-dashboard.models';
import { AdminDashboardService } from './admin-dashboard.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './admin-dashboard.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminDashboardComponent {
  private readonly dashboardService = inject(AdminDashboardService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly isLoading = signal(true);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly data = signal<AdminDashboardViewData | null>(null);
  protected readonly paymentTotal = computed(() => {
    const status = this.data()?.dashboard.report.payment_status;
    return status ? status.paid + status.partial + status.unpaid : 0;
  });
  protected readonly paidPercentage = computed(() => {
    const total = this.paymentTotal();
    return total ? Math.round(((this.data()?.dashboard.report.payment_status.paid ?? 0) / total) * 100) : 0;
  });
  protected readonly paymentChart = computed(() => {
    const status = this.data()?.dashboard.report.payment_status;
    const total = this.paymentTotal();
    if (!status || total === 0) return 'conic-gradient(#e1e3e4 0 100%)';

    const paid = (status.paid / total) * 100;
    const partial = paid + (status.partial / total) * 100;
    return `conic-gradient(#005d90 0 ${paid}%, #00677d ${paid}% ${partial}%, #ba1a1a ${partial}% 100%)`;
  });

  constructor() {
    this.load();
  }

  protected load(): void {
    this.isLoading.set(true);
    this.errorMessage.set(null);

    this.dashboardService
      .load()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading.set(false)),
      )
      .subscribe({
        next: (data) => this.data.set(data),
        error: () => this.errorMessage.set('Não foi possível carregar o dashboard. Tente novamente.'),
      });
  }

  protected paymentLabel(status: RecentAttendance['payment_status']): string {
    return { paid: 'Pago', partial: 'Parcial', unpaid: 'Não pago' }[status];
  }

  protected paymentClasses(status: RecentAttendance['payment_status']): string {
    return {
      paid: 'bg-emerald-50 text-emerald-700',
      partial: 'bg-amber-50 text-amber-700',
      unpaid: 'bg-red-50 text-[#ba1a1a]',
    }[status];
  }

  protected procedureNames(attendance: RecentAttendance): string {
    return attendance.procedures.map((procedure) => procedure.procedure).join(', ');
  }
}
