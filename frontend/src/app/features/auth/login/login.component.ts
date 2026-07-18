import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';

import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly showPassword = signal(false);
  protected readonly isLoading = signal(false);
  protected readonly loginError = signal<string | null>(null);

  protected readonly loginForm = new FormGroup({
    email: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.email],
    }),
    password: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required],
    }),
  });

  protected togglePasswordVisibility(): void {
    this.showPassword.update((visible) => !visible);
  }

  protected submit(): void {
    this.loginError.set(null);

    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    this.isLoading.set(true);
    this.loginForm.disable();

    this.authService
      .login(this.loginForm.getRawValue())
      .pipe(
        finalize(() => {
          this.isLoading.set(false);
          this.loginForm.enable();
        }),
      )
      .subscribe({
        next: () => void this.router.navigateByUrl(this.authService.dashboardUrl()),
        error: (error: HttpErrorResponse) => this.loginError.set(this.errorMessage(error)),
      });
  }

  private errorMessage(error: HttpErrorResponse): string {
    if (error.status === 401) {
      return 'Credenciais inválidas. Verifique o seu e-mail e palavra-passe.';
    }

    if (error.status === 403) {
      return 'A sua conta está bloqueada. Contacte o administrador.';
    }

    if (error.status === 0) {
      return 'Não foi possível ligar ao servidor. Tente novamente.';
    }

    return 'Não foi possível iniciar sessão. Tente novamente mais tarde.';
  }
}
