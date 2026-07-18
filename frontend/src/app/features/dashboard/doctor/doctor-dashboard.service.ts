import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, forkJoin, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import {
  AttendanceCollectionResponse,
  DoctorDashboardResponse,
  DoctorDashboardViewModel,
  DoctorProfileResponse,
} from './doctor-dashboard.models';

@Injectable({ providedIn: 'root' })
export class DoctorDashboardService {
  private readonly http = inject(HttpClient);
  private readonly dayFormatter = new Intl.DateTimeFormat('pt-AO', { weekday: 'short', timeZone: 'Africa/Luanda' });

  load(today: string): Observable<DoctorDashboardViewModel> {
    const attendanceParams = new HttpParams()
      .set('date_from', today)
      .set('date_to', today)
      .set('per_page', 100);

    return forkJoin({
      dashboard: this.http.get<DoctorDashboardResponse | null>(`${environment.apiUrl}/dashboard`),
      profile: this.http.get<DoctorProfileResponse | null>(`${environment.apiUrl}/doctor/profile`),
      attendances: this.http.get<AttendanceCollectionResponse | null>(`${environment.apiUrl}/attendances`, {
        params: attendanceParams,
      }),
    }).pipe(
      map(({ dashboard, profile, attendances }) => {
        if (!dashboard || !profile || !attendances) throw new Error('EMPTY_RESPONSE');
        if (dashboard.scope !== 'doctor' || dashboard.doctor.id !== profile.data.id) {
          throw new Error('DOCTOR_SCOPE_MISMATCH');
        }
        if (attendances.data.some((attendance) => attendance.doctor.id !== profile.data.id)) {
          throw new Error('DOCTOR_SCOPE_MISMATCH');
        }
        return this.toViewModel(dashboard, profile, attendances, today);
      }),
    );
  }

  private toViewModel(
    dashboard: DoctorDashboardResponse,
    profile: DoctorProfileResponse,
    attendances: AttendanceCollectionResponse,
    today: string,
  ): DoctorDashboardViewModel {
    const firstDay = new Date(`${today}T12:00:00`);
    firstDay.setDate(firstDay.getDate() - 6);
    const firstDayIso = new Intl.DateTimeFormat('en-CA', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Africa/Luanda',
    }).format(firstDay);
    const flow = dashboard.daily_patient_flow.filter((point) => point.attendance_date >= firstDayIso);
    const maxFlow = Math.max(...flow.map((point) => Number(point.total)), 0);
    const maxProcedures = Math.max(...dashboard.top_procedures_this_month.map((item) => Number(item.total_performed)), 0);

    return {
      speciality: profile.data.speciality,
      statistics: [
        { label: 'Pacientes hoje', value: dashboard.statistics.today.patients_count, icon: 'groups', tone: 'bg-[#e4f3fa] text-[#005d90]' },
        { label: 'Pacientes esta semana', value: dashboard.statistics.week.patients_count, icon: 'date_range', tone: 'bg-[#e1f5f8] text-[#00677d]' },
        { label: 'Pacientes este mês', value: dashboard.statistics.month.patients_count, icon: 'calendar_month', tone: 'bg-[#ecf1ff] text-[#405f91]' },
        { label: 'Média diária', value: null, icon: 'monitoring', tone: 'bg-[#e8f5ed] text-[#2d6a45]' },
      ],
      patientFlow: flow.map((point) => ({
        label: this.dayFormatter.format(new Date(`${point.attendance_date}T12:00:00`)).replace('.', ''),
        value: Number(point.total),
        percentage: maxFlow ? Math.max((Number(point.total) / maxFlow) * 100, 4) : 0,
      })),
      ageDistribution: [],
      todayPatients: attendances.data.map((attendance) => ({
        attendanceId: attendance.id,
        patient: attendance.patient.name,
        age: null,
        time: null,
        procedures: attendance.procedures.map((procedure) => procedure.procedure),
        status: null,
      })),
      appointments: [],
      frequentProcedures: dashboard.top_procedures_this_month.map((procedure) => ({
        id: procedure.id,
        name: procedure.procedure,
        count: Number(procedure.total_performed),
        percentage: maxProcedures ? (Number(procedure.total_performed) / maxProcedures) * 100 : 0,
      })),
      commissions: {
        today: this.money(dashboard.statistics.today.commission_amount),
        week: this.money(dashboard.statistics.week.commission_amount),
        month: this.money(dashboard.statistics.month.commission_amount),
      },
    };
  }

  private money(value: string): number | null {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }
}
