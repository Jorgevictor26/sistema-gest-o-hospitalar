import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login/login.component').then((component) => component.LoginComponent),
  },
  { path: '', pathMatch: 'full', redirectTo: 'login' },
  { path: 'dashboard' },
  { path: '**', redirectTo: 'login' },
];
