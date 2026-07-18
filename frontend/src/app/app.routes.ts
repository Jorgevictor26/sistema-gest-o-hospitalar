import { Routes } from '@angular/router';

import { authGuard, dashboardRedirectGuard } from './core/auth/auth.guard';
import { roleGuard } from './core/auth/role.guard';
import { AdminDashboardComponent } from './features/dashboard/admin/admin-dashboard.component';
import { CreateAttendanceComponent } from './features/attendances/create-attendance/create-attendance.component';
import { DailyClosingComponent } from './features/daily-closing/daily-closing.component';
import { EmptyDashboardComponent } from './features/dashboard/empty-dashboard.component';
import { DoctorDashboardComponent } from './features/dashboard/doctor/doctor-dashboard.component';
import { ReceptionistDashboardComponent } from './features/dashboard/receptionist/receptionist-dashboard.component';
import { LoginComponent } from './features/auth/login/login.component';
import { AppLayoutComponent } from './layout/app-layout/app-layout.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  {
    path: 'dashboard',
    component: AppLayoutComponent,
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', canActivate: [dashboardRedirectGuard], component: EmptyDashboardComponent },
      {
        path: 'admin',
        component: AdminDashboardComponent,
        canActivate: [roleGuard],
        data: {
          roles: ['admin'],
          title: 'Visão geral',
          subtitle: 'Acompanhe o movimento e o desempenho do centro médico.',
        },
      },
      {
        path: 'receptionist',
        component: ReceptionistDashboardComponent,
        canActivate: [roleGuard],
        data: { roles: ['receptionist'], title: 'Visão geral', subtitle: 'Dashboard da receção.' },
      },
      {
        path: 'doctor',
        component: DoctorDashboardComponent,
        canActivate: [roleGuard],
        data: { roles: ['doctor'], title: 'Visão geral', subtitle: 'Dashboard do médico.' },
      },
    ],
  },
  {
    path: 'attendances',
    component: AppLayoutComponent,
    canActivate: [authGuard],
    children: [
      {
        path: 'new',
        component: CreateAttendanceComponent,
        canActivate: [roleGuard],
        data: {
          roles: ['admin', 'receptionist'],
          title: 'Novo atendimento',
          subtitle: 'Registe os dados do paciente e os procedimentos realizados.',
        },
      },
    ],
  },
  {
    path: 'daily-closing',
    component: AppLayoutComponent,
    canActivate: [authGuard],
    children: [
      {
        path: '',
        component: DailyClosingComponent,
        canActivate: [roleGuard],
        data: {
          roles: ['admin', 'receptionist'],
          title: 'Fecho diário',
          subtitle: 'Consulte o resumo financeiro e o estado do dia.',
        },
      },
    ],
  },
  { path: 'daily-closures', pathMatch: 'full', redirectTo: 'daily-closing' },
  { path: '', pathMatch: 'full', redirectTo: 'login' },
  { path: '**', redirectTo: 'login' },
];
