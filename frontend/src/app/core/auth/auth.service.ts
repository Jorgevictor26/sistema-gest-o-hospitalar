import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { environment } from '../../../environments/environment';
import { LoginCredentials, LoginResponse } from './auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokenStorageKey = 'medical_center_auth_token';
  private readonly userStorageKey = 'medical_center_auth_user';
  private readonly userState = signal(this.readStoredUser());

  readonly currentUser = this.userState.asReadonly();
  readonly isAuthenticated = computed(() => this.getToken() !== null && this.currentUser() !== null);

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(`${environment.apiUrl}/auth/login`, credentials)
      .pipe(tap((response) => this.storeSession(response)));
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenStorageKey);
  }

  hasRole(role: string): boolean {
    return this.currentUser()?.roles.includes(role) ?? false;
  }

  dashboardUrl(): string {
    const roles = this.currentUser()?.roles ?? [];

    if (roles.includes('admin')) return '/dashboard/admin';
    if (roles.includes('receptionist')) return '/dashboard/receptionist';
    if (roles.includes('doctor')) return '/dashboard/doctor';

    return '/login';
  }

  logout(): void {
    localStorage.removeItem(this.tokenStorageKey);
    localStorage.removeItem(this.userStorageKey);
    this.userState.set(null);
  }

  private storeSession({ token, user }: LoginResponse): void {
    localStorage.setItem(this.tokenStorageKey, token);
    localStorage.setItem(this.userStorageKey, JSON.stringify(user));
    this.userState.set(user);
  }

  private readStoredUser(): LoginResponse['user'] | null {
    const storedUser = localStorage.getItem(this.userStorageKey);

    if (!storedUser) return null;

    try {
      return JSON.parse(storedUser) as LoginResponse['user'];
    } catch {
      localStorage.removeItem(this.userStorageKey);
      return null;
    }
  }
}
