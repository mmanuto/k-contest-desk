import { Injectable } from '@angular/core';
import { Subject, Observable } from 'rxjs';

@Injectable()
export class DisplayInfoService
{
    private channel: BroadcastChannel;
    private displayWindow: Window | null = null;
    
    // Observable per la finestra ricevente (il monitor)
  private dataSubject = new Subject<any>();
  public data$ = this.dataSubject.asObservable();

  constructor() {
    // 1. Crea il canale univoco
    this.channel = new BroadcastChannel('display-channel');

    // 2. Ascolta i messaggi (per la finestra RICEVENTE)
    this.channel.onmessage = (event) => {
      this.dataSubject.next(event.data);
    };
  }

  /**
   * Chiamato dal componente PADRE (Admin)
   * Invia i dati e apre la finestra se non c'è.
   */
  public updateDisplay(data: any, url: string) {
    // A. Invia i dati sul canale
    this.channel.postMessage(data);

    // B. Gestione finestra (evita di aprirne 1000)
    if (!this.displayWindow || this.displayWindow.closed) {
      this.displayWindow = window.open(url, 'DisplayMonitor', 'width=1000,height=800,toolbar=no,menubar=no');
      
      // Piccolo trick: invia di nuovo i dati dopo un attimo perché 
      // la finestra appena aperta potrebbe non essere pronta a ricevere subito
      setTimeout(() => {
        this.channel.postMessage(data);
      }, 1000);
    } else {
      this.displayWindow.focus();
    }
  }

  /**
   * Metodo per chiudere il canale quando l'app si distrugge
   */
  public close() {
    this.channel.close();
  }

}
