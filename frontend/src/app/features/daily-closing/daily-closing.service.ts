import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  DailyClosureStatusResponse,
  PaginatedAttendancesApiResponse,
} from './daily-closing.api-models';

@Injectable({ providedIn: 'root' })
export class DailyClosingService {
  private readonly http = inject(HttpClient);

  getByDate(date: string): Observable<DailyClosureStatusResponse> {
    return this.http.get<DailyClosureStatusResponse>(
      `${environment.apiUrl}/daily-closures/${date}/status`,
    );
  }

  getAttendancesByDate(
    date: string,
    page: number,
    perPage: number,
    search?: string,
    paymentStatus?: 'paid' | 'partial' | 'unpaid',
  ): Observable<PaginatedAttendancesApiResponse> {
    let params = new HttpParams()
      .set('date_from', date)
      .set('date_to', date)
      .set('page', page)
      .set('per_page', perPage);
    if (search) params = params.set('search', search);
    if (paymentStatus) params = params.set('payment_status', paymentStatus);

    return this.http.get<PaginatedAttendancesApiResponse>(`${environment.apiUrl}/attendances`, {
      params,
    });
  }
}
