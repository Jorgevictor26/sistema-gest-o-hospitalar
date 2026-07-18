import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, forkJoin, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { AdminDashboardResponse } from '../admin/admin-dashboard.models';
import {
  AttendanceFilters,
  DailyClosure,
  DailyClosureStatus,
  DoctorOption,
  PaginatedAttendances,
  ReceptionistDashboardData,
} from './receptionist-dashboard.models';

@Injectable({ providedIn: 'root' })
export class ReceptionistDashboardService {
  private readonly http = inject(HttpClient);

  load(date: string, filters: AttendanceFilters): Observable<ReceptionistDashboardData> {
    return forkJoin({
      dashboard: this.http
        .get<AdminDashboardResponse | null>(`${environment.apiUrl}/dashboard`, {
          params: new HttpParams().set('period', 'daily').set('date', date),
        })
        .pipe(map((response) => this.requireResponse(response))),
      closureStatus: this.http
        .get<DailyClosureStatus | null>(`${environment.apiUrl}/daily-closures/${date}/status`)
        .pipe(map((response) => this.requireResponse(response))),
      attendances: this.getAttendances(date, filters),
      doctors: this.http
        .get<{ data: DoctorOption[] } | null>(`${environment.apiUrl}/doctors`, {
          params: new HttpParams().set('active', true).set('per_page', 100),
        })
        .pipe(map((response) => response?.data ?? [])),
    });
  }

  getAttendances(date: string, filters: AttendanceFilters): Observable<PaginatedAttendances> {
    let params = new HttpParams()
      .set('date_from', date)
      .set('date_to', date)
      .set('per_page', 10)
      .set('page', filters.page);

    if (filters.search) params = params.set('search', filters.search);
    if (filters.doctorId) params = params.set('doctor_id', filters.doctorId);
    if (filters.paymentStatus) params = params.set('payment_status', filters.paymentStatus);

    return this.http
      .get<PaginatedAttendances | null>(`${environment.apiUrl}/attendances`, { params })
      .pipe(map((response) => this.requireResponse(response)));
  }

  closeDay(date: string): Observable<{ data: DailyClosure }> {
    return this.http.post<{ data: DailyClosure }>(`${environment.apiUrl}/daily-closures`, { date });
  }

  private requireResponse<T>(response: T | null): T {
    if (response === null) throw new Error('EMPTY_RESPONSE');
    return response;
  }
}
