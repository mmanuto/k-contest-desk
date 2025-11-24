import { Component, ViewEncapsulation, OnInit, Pipe, PipeTransform } from '@angular/core';
import { DisplayInfoService } from './displayinfo.service';
import { interval, Subscription} from 'rxjs';
import {monitor} from '../../constants';
import { LocalStorageService } from 'angular-2-local-storage';
declare var jQuery: any;

@Component({
  selector: 'displayinfo',
  templateUrl: './displayinfo.component.html',
  styleUrls: ['./displayinfo.scss'],
  encapsulation: ViewEncapsulation.None
})

export class DisplayInfoComponent implements OnInit {

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
    protected localStorageService: LocalStorageService) {
      this.mySubscription= interval(500).subscribe((x =>{
        this.checkAthlete();
    }));    
  }
  
  ngOnInit() {
    
    /*var elem = document.documentElement;
      elem.requestFullscreen();*/
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

    }
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
      } else {
        console.log('fineeee');
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
