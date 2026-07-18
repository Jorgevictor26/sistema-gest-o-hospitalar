import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthService } from './auth.service';

export const roleGuard: CanActivateFn = (route) => {
  const authService = inject(AuthService);
  const allowedRoles = (route.data['roles'] as string[] | undefined) ?? [];

  if (allowedRoles.some((role) => authService.hasRole(role))) return true;

  return inject(Router).parseUrl(authService.dashboardUrl());
};
