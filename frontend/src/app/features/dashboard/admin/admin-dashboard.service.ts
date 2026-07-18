import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, forkJoin, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import {
  AdminDashboardResponse,
  AdminDashboardViewData,
  AttendanceCollectionResponse,
} from './admin-dashboard.models';

@Injectable({ providedIn: 'root' })
export class AdminDashboardService {
  private readonly http = inject(HttpClient);

  load(): Observable<AdminDashboardViewData> {
    const dashboardParams = new HttpParams().set('period', 'daily');
    const attendanceParams = new HttpParams().set('per_page', 5);

    return forkJoin({
      dashboard: this.http.get<AdminDashboardResponse>(`${environment.apiUrl}/dashboard`, {
        params: dashboardParams,
      }),
      attendances: this.http.get<AttendanceCollectionResponse>(`${environment.apiUrl}/attendances`, {
        params: attendanceParams,
      }),
    }).pipe(
      map(({ dashboard, attendances }) => ({
        dashboard,
        recentAttendances: attendances.data,
      })),
    );
  }
}
