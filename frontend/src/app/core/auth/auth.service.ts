import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { environment } from '../../../environments/environment';
import { LoginCredentials, LoginResponse } from './auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokenStorageKey = 'medical_center_auth_token';

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(`${environment.apiUrl}/auth/login`, credentials)
      .pipe(tap(({ token }) => this.storeToken(token)));
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenStorageKey);
  }

  private storeToken(token: string): void {
    localStorage.setItem(this.tokenStorageKey, token);
  }
}
