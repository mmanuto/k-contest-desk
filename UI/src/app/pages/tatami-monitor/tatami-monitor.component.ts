import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subscription, timer } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { TatamiMonitorService } from './tatami-monitor.service';
import { TatamiStatus } from './tatami.model';

@Component({
  selector: 'app-tatami-monitor',
  templateUrl: './tatami-monitor.component.html',
  styleUrls: ['./tatami-monitor.component.scss']
})
export class TatamiMonitorComponent {

  tatamis: TatamiStatus[] = [];
  subscription!: Subscription;
  lastUpdate: Date = new Date();

  constructor(private tatamiMonitorService: TatamiMonitorService) {}

  ngOnInit(): void {
    // Esegue la chiamata subito (0) e poi ogni 30 secondi (30000)
    this.subscription = timer(0, 30000).pipe(
      switchMap(() => this.tatamiMonitorService.getTatamiStatus())
    ).subscribe({
      next: (data) => {
        this.tatamis = data;
        this.lastUpdate = new Date();
      },
      error: (err) => console.error('Errore monitor tatami', err)
    });
  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
  }

  // Helper per calcolare il colore della progress bar
  getProgressColor(percent: number, isClosing: boolean): string {
    if (isClosing) return 'var(--warning-color)'; // Arancione se sta finendo
    if (percent < 50) return 'var(--primary-color)';
    return 'var(--success-color)';
  }

  // Helper per stimare il tempo (opzionale)
  getEstimatedTime(t: TatamiStatus): string {
    if (t.status === 'LIBERO') return '';
    
    // Stima minuti per incontro/atleta
    let minutesPerUnit = 2; 
    if (t.specialty === 'KUMITE') minutesPerUnit = 3;
    if (t.specialty === 'KATA') minutesPerUnit = 4;
    
    const totalMinutes = t.remaining * minutesPerUnit;
    return `~${totalMinutes} min`;
  }


}
