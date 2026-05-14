import { Component, ViewEncapsulation, OnInit, Pipe, PipeTransform, ChangeDetectorRef } from '@angular/core';
import { DisplayInfoService } from './displayinfo.service';
import { interval, Subscription} from 'rxjs';
import {categoryStatus, colorMapping, monitor, prova, categoryPhase, RoundMatch, RoundMatchTitles} from '../../constants';
import { LocalStorageService } from 'angular-2-local-storage';
declare var jQuery: any;

@Component({
  selector: 'displayinfo',
  templateUrl: './displayinfo.component.html',
  styleUrls: ['./displayinfo.scss'],
  encapsulation: ViewEncapsulation.None
})

export class DisplayInfoComponent implements OnInit {


  athleteData: any = null;
  categoryPhase = categoryPhase;
  currentPhase = '';



  mySubscription: Subscription
  currentUser;
  athlete_1 = null;
  athlete_2 = null;
  athleteList = null;
  currentMatch = null;
  countdown;
  interval;
  action = 'PAUSE';

  displayName_1 = '';

  constructor(
    protected localStorageService: LocalStorageService,
    private displayService: DisplayInfoService,
    private cd: ChangeDetectorRef // Importante per aggiornare la UI
  ) { 
  }
  
  ngOnInit() {

    // Ci iscriviamo all'Observable del servizio
    this.displayService.data$.subscribe(data => {
      console.log('Dati ricevuti sul monitor:', data);
      
      this.athleteData = data;
      
      // Forza l'aggiornamento della vista perché l'evento arriva dall'esterno di Angular
      this.cd.detectChanges();
    });

    

    this.currentUser = this.localStorageService.get('currentUser');

    
    /*var elem = document.documentElement;
      elem.requestFullscreen();
    var card = jQuery('.card_body');
    this.currentUser = this.localStorageService.get('currentUser');
    if(this.localStorageService.get(monitor.ATHLETE_1)){
      this.athlete_1 = this.localStorageService.get(monitor.ATHLETE_1);
      this.displayName_1 = `${this.athlete_1['athlete']['nome']} ${this.athlete_1['athlete']['cognome']}`;
      if(this.athlete_1['scores']['total']){
        this.displayName_1 = `${this.athlete_1['athlete']['nome']} ${this.athlete_1['athlete']['cognome']}  -  ${this.athlete_1['scores']['total']}`;
      }
      this.athlete_2 = this.localStorageService.get(monitor.ATHLETE_2)?? null;
      this.athleteList = null;

      setTimeout(function(){
        card.addClass('active');
      }, 500);
    }else if(this.localStorageService.get('classifica')){
      this.athleteList = this.localStorageService.get('classifica');
      this.athlete_1 = null;
      card.addClass('hidden');
    }else if(this.localStorageService.get(monitor.KUMITE)){
      console.log('entraaaaaaaaaaa');
      this.currentMatch = this.localStorageService.get(monitor.KUMITE);
      this.countdown = this.localStorageService.get(monitor.COUNTDOWN);
      console.log(this.currentMatch);
      this.athlete_1 = null;
      card.addClass('hidden');

      this.displayName_1 = `${this.currentMatch.aka.categorycode.specialita} ${this.currentMatch.aka.categorycode.categoria}`;

    }*/
  }

  ngAfterViewInit(){
    document.getElementById('preloader').classList.add('hide');
}

  checkAthlete(){
    
    if(this.athlete_1 && this.localStorageService.get(monitor.ATHLETE_1)){
      if(this.athlete_1['id'] != this.localStorageService.get(monitor.ATHLETE_1)['id'] || 
      this.athlete_1['scores']['total'] != this.localStorageService.get(monitor.ATHLETE_1)['scores']['total']){

      window.top.location.reload();
      }
    }else if(this.athlete_1 == null && this.localStorageService.get(monitor.ATHLETE_1) ||
      this.athlete_1 && this.localStorageService.get(monitor.ATHLETE_1) == null ||
      this.athleteList == null && this.localStorageService.get('classifica') ||
      this.athleteList && this.localStorageService.get('classifica') == null){

      window.top.location.reload();
    }else if(this.athleteList && this.localStorageService.get('classifica') && this.localStorageService.get('classifica')[0]['id'] != this.athleteList[0]['id']){
      window.location.reload();

    }

    if(this.currentMatch && this.localStorageService.get(monitor.KUMITE)){
      console.log('entra 1');
      if(this.currentMatch != this.localStorageService.get(monitor.KUMITE)){
        console.log('changeeeeeeeeeee');
        this.currentMatch = this.localStorageService.get(monitor.KUMITE);
      }
      if(this.action != this.localStorageService.get('ACTION') || this.action == 'MODIFY'){
        this.action = this.localStorageService.get('ACTION');
        if(this.action == 'START'){
          this.countdown = this.localStorageService.get(monitor.COUNTDOWN);
          this.startTimer();
        }else if(this.action == 'PAUSE'){
          this.pauseTimer();
        }else if(this.action == 'MODIFY'){
          this.countdown = this.localStorageService.get(monitor.COUNTDOWN);
        }
        
      }
    }
    
  }

  startTimer() {
    if(this.countdown > 0) {
      this.countdown--;
    }
    this.interval = setInterval(() => {
      if(this.countdown > 0) {
        this.countdown--;
        if(this.countdown == 15){
          this.playWarningBeep();
        }
      } else {
        console.log('fineeee');
        this.playFinalBuzzer();
        if(this.currentMatch.aka.scores.total > this.currentMatch.ao.scores.total){
          this.currentMatch.aka.scores.win = 1;
          this.currentMatch.ao.scores.win = 0;
        }else if(this.currentMatch.aka.scores.total < this.currentMatch.ao.scores.total){
          this.currentMatch.aka.scores.win = 0;
          this.currentMatch.ao.scores.win = 1;
        }else{
          if(this.currentMatch.aka.scores.senshu || this.currentMatch.ao.scores.senshu){
            this.currentMatch.aka.scores.win = this.currentMatch.aka.scores.senshu;
            this.currentMatch.ao.scores.win = this.currentMatch.ao.scores.senshu;
          }else{
            console.log('Parità');
          }
        }
        console.log('finitoooooooooooooooooooooooo');
      }
    },1000)
  }

  pauseTimer() {
    clearInterval(this.interval);
  }


  /**
   * Genera un segnale acustico elettronico (Beep)
   * @param duration Durata in millisecondi
   * @param frequency Frequenza in Hz (es. 440 per un tono standard, 880 per uno più acuto)
   */
  private playBeep(duration: number, frequency: number) {
      const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
      const oscillator = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioCtx.destination);

      oscillator.type = 'sine'; // Tono pulito
      oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
      
      // Gestione volume per evitare "click" audio all'inizio/fine
      gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration / 1000);

      oscillator.start();
      oscillator.stop(audioCtx.currentTime + duration / 1000);
  }

  // Funzione per il segnale singolo (15 secondi)
  private playWarningBeep() {
      this.playBeep(200, 440); // 440Hz è un LA standard
  }

  // Funzione per il segnale doppio (Fine tempo)
  private playFinalBuzzer() {
      this.playBeep(150, 600); // Primo beep
      setTimeout(() => {
          this.playBeep(150, 600); // Secondo beep dopo 100ms
      }, 250);
  }
}




@Pipe({
  name: "formatTime"
})
export class FormatTimePipe implements PipeTransform {
  transform(value: number): string {
    const minutes: number = Math.floor(value / 60);
    return (
      ("00" + minutes).slice(-2) +
      ":" +
      ("00" + Math.floor(value - minutes * 60)).slice(-2)
    );
  }
}


