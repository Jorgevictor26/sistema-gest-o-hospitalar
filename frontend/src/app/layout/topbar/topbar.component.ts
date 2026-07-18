import { ChangeDetectionStrategy, Component, EventEmitter, Output, inject } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { ActivatedRoute, NavigationEnd, Router, RouterLink } from '@angular/router';
import { filter, map, startWith } from 'rxjs';

import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-topbar',
  standalone: true,
  imports: [AsyncPipe, RouterLink],
  templateUrl: './topbar.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TopbarComponent {
  private readonly router = inject(Router);
  private readonly activatedRoute = inject(ActivatedRoute);
  protected readonly authService = inject(AuthService);

  @Output() readonly menuToggle = new EventEmitter<void>();

  protected readonly pageData$ = this.router.events.pipe(
    filter((event) => event instanceof NavigationEnd),
    startWith(null),
    map(() => {
      let route = this.activatedRoute;
      while (route.firstChild) route = route.firstChild;
      return {
        title: route.snapshot.data['title'] ?? 'Visão geral',
        subtitle: route.snapshot.data['subtitle'] ?? 'Gestão do centro médico.',
      };
    }),
  );
}
