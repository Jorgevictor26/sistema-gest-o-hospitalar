import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-empty-dashboard',
  standalone: true,
  template: '<div class="min-h-[calc(100dvh-4rem)]" aria-hidden="true"></div>',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EmptyDashboardComponent {}
