import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output, computed, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';

interface NavigationItem {
  label: string;
  route: string;
  icon: string;
}

const MENUS: Record<string, NavigationItem[]> = {
  admin: [
    { label: 'Visão geral', route: '/dashboard/admin', icon: '▦' },
    { label: 'Atendimentos', route: '/attendances', icon: '▤' },
    { label: 'Marcações', route: '/appointments', icon: '□' },
    { label: 'Fecho diário', route: '/daily-closing', icon: '◫' },
    { label: 'Pacientes', route: '/patients', icon: '○' },
    { label: 'Médicos', route: '/doctors', icon: '✚' },
    { label: 'Procedimentos', route: '/procedures', icon: '◇' },
    { label: 'Utilizadores', route: '/users', icon: '◎' },
    { label: 'Relatórios', route: '/reports', icon: '⌁' },
    { label: 'Definições', route: '/settings', icon: '⚙' },
  ],
  receptionist: [
    { label: 'Visão geral', route: '/dashboard/receptionist', icon: '▦' },
    { label: 'Novo atendimento', route: '/attendances/new', icon: '+' },
    { label: 'Atendimentos', route: '/attendances', icon: '▤' },
    { label: 'Marcações', route: '/appointments', icon: '□' },
    { label: 'Fecho diário', route: '/daily-closing', icon: '◫' },
    { label: 'Pacientes', route: '/patients', icon: '○' },
    { label: 'Relatórios', route: '/reports', icon: '⌁' },
  ],
  doctor: [
    { label: 'Visão geral', route: '/dashboard/doctor', icon: '▦' },
    { label: 'Minha agenda', route: '/doctor/agenda', icon: '□' },
    { label: 'Meus atendimentos', route: '/attendances', icon: '▤' },
    { label: 'Meus pacientes', route: '/doctor/patients', icon: '○' },
    { label: 'Minhas estatísticas', route: '/doctor/statistics', icon: '⌁' },
    { label: 'Minhas comissões', route: '/doctor/commissions', icon: '◫' },
    { label: 'Diretório médico', route: '/doctors', icon: '✚' },
  ],
};

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './sidebar.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SidebarComponent {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  @Input() open = false;
  @Output() readonly closed = new EventEmitter<void>();

  protected readonly user = this.authService.currentUser;
  protected readonly menuItems = computed(() => MENUS[this.primaryRole()] ?? []);
  protected readonly initials = computed(() =>
    (this.user()?.name ?? 'U')
      .split(' ')
      .slice(0, 2)
      .map((part) => part[0])
      .join('')
      .toUpperCase(),
  );

  protected close(): void {
    this.closed.emit();
  }

  protected logout(): void {
    this.authService.logout();
    void this.router.navigateByUrl('/login');
  }

  private primaryRole(): string {
    const roles = this.user()?.roles ?? [];
    return ['admin', 'receptionist', 'doctor'].find((role) => roles.includes(role)) ?? '';
  }
}
