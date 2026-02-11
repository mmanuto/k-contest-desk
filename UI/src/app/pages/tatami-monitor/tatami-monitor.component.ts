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
    this.subscription = timer(0, 25000).pipe(
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

  // Traduce lo stato tecnico del DB in etichetta leggibile
  getPhaseLabel(phase: string): string {
    if (!phase) return '';

    switch (phase) {
      case 'judgePannel':
      case 'judgePanel': // Gestiamo eventuali typo storici
        return 'Punteggi'; // O "Gironi"
        
      case 'brackets':
      case 'ResultsMatch': // Se usi questo termine per i match
        return 'Scontri Diretti'; // O "Tabellone"
        
      case 'awaitingBrackets':
        return 'Attesa Tabellone';

      case 'finalized':
        return 'Terminata';
        
      default:
        return phase; // Se non lo conosciamo, mostriamo quello che arriva
    }
  }


  getEstimatedTime(t: TatamiStatus): string {
    // Se è libero, non c'è tempo da stimare
    if (t.status === 'LIBERO') return '';

    let totalMinutes = 0;

    // 1. Uniamo le liste per calcolare tutto in un unico ciclo
    // (Usiamo lo spread operator ... per combinare gli array)
    const allCategories = [...t.active_categories, ...t.queued_categories];

    // 2. Cicliamo su ogni categoria per sommare i minuti
    for (const cat of allCategories) {
      let minutesPerUnit = 2; // Default (Percorso, Palloncino, ecc.)


      if (cat.label === 'Incontri' || cat.label.includes('Match')) {
        minutesPerUnit = 5; // Un match di Kata dura un po' di più (saluto, esecuzione, bandierine)
      } else if (cat.specialty.includes('KATA')) {
        minutesPerUnit = 4; // Esecuzione singola a punteggio
      }

      // Aggiungi al totale: (rimanenti * minuti_per_unità)
      totalMinutes += (cat.remaining * minutesPerUnit);
    }

    // Se per qualche motivo il totale è 0 (es. tutto finito ma non ancora chiuso), non mostrare nulla
    if (totalMinutes <= 0) return '';

    return `~${totalMinutes} min`;
  }

  // Helper per assegnare un colore alla badge in base al testo della specialità
  getSpecialtyClass(specialty: string): string {
    if (!specialty) return 'badge-secondary'; // Default Grigio

    const s = specialty.toUpperCase();
/*
    if (s.includes('KUMITE')) {
      return 'badge-kumite'; // Rosso per combattimento
    } 
    else if (s.includes('KATA')) {
      return 'badge-kata'; // Azzurro per forma (text-white per sicurezza)
    } 
    else if (s.includes('PALLONCINO') || s.includes('PERCORSO')) {
      return 'badge-combinata'; // Verde per i giochi bambini
    }*/

    return 'specialty-label'; // Grigio per altro
  }


}
