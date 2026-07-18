import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = () => {
  const authService = inject(AuthService);

  return authService.isAuthenticated() || inject(Router).createUrlTree(['/login']);
};

export const dashboardRedirectGuard: CanActivateFn = () => {
  const authService = inject(AuthService);

  return inject(Router).parseUrl(authService.dashboardUrl());
};
