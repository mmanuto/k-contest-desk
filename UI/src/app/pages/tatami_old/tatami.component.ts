import { Component, ViewEncapsulation, ViewChild, Pipe, PipeTransform, ElementRef  } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormControl, FormGroup, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { LocalStorageService } from 'angular-2-local-storage';
import { TatamiService } from './tatami.service';
import * as Constants from '../../constants';
import {interval,Subscription} from 'rxjs';
import {ModalDirective} from 'ngx-bootstrap/modal';
import * as Dataset from './datasets';
import { InMemoryDatabase } from './storage/memory';
import { BracketsManager } from 'brackets-manager';
import { environment } from '../../../environments/environment';
import { DisplayInfoComponent } from '../displayinfo/displayinfo.component';
import { countries } from '../dashboard/dashboard.data';

declare global {
  interface JQuery {
    (any): JQuery;
    bracket(options: any): JQuery;
  }
}

const TOURNAMENT_ID = 0;

function getNearestPowerOfTwo(input: number): number {
  return Math.pow(2, Math.ceil(Math.log2(input)));
}

async function process(dataset: Dataset) {
  const db = new InMemoryDatabase();
  const manager = new BracketsManager(db);

  db.setData({
    participant: dataset.roster.map((player) => ({
      ...player,
      tournament_id: TOURNAMENT_ID,
    })),
    stage: [],
    group: [],
    round: [],
    match: [],
    match_game: [],
  });

  await manager.create({
    name: dataset.title,
    tournamentId: TOURNAMENT_ID,
    type: dataset.type,
    seeding: dataset.roster.map((player) => player.name),
    settings: {
      seedOrdering: ['natural'],
      size: getNearestPowerOfTwo(dataset.roster.length),
    },
  });

  const data = await manager.get.stageData(0);

  return {
    stages: data.stage,
    matches: data.match,
    matchGames: data.match_game,
    participants: data.participant,
    data: data
  };
}


@Component({
  selector: 'tatami',
  templateUrl: './tatami.component.html',
  styleUrls: ['./tatami.component.scss'],
  encapsulation: ViewEncapsulation.None
})

export class TatamiComponent {

  @ViewChild('dangerModal') public dangerModal: ModalDirective;
  @ViewChild('matchModal') public matchModal: ModalDirective;
  @ViewChild('doubleKataModal') public doubleKataModal: ModalDirective;
  @ViewChild('kumiteModal') public kumiteModal: ModalDirective;

  public searchText: string;
  categories: any[];
  athleteList: any[];
  tatamiStatus: any[];
  selectedCategory: any;
  labelCategory: string = '';
  alertsDismiss: any = [];
  colorMapping = Constants.colorMapping;
  currentUser;
  typeForm;
  constants = Constants;
  kataList: any = [];
  mySubscription: Subscription;
  tempFormRequest: any = [];
  previousMatches = [];
  kumiteAthleteList: any[];
  kumiteMatches: any = [];
  currentMatch:any = [];
  message = '';
  dataKata= [];
  showStartButton = true;

  countdown: number = 120;
  interval;

  minimalData = {};
  secondGirone = {};
  thirdGirone = {};
  firstMatch = [];
  secondMatch = [];
  thirdMatch = [];

  constructor(private tatamiService: TatamiService, 
    public fb: FormBuilder,
    protected localStorageService: LocalStorageService) { 
      this.mySubscription= interval(20000).subscribe((x =>{
        this.getTatamiStatus();
    })); 
  }

  // ------------------------------------------------- INITIALIZATION ----------------------------------------

  ngOnInit() {

    this.currentUser = this.localStorageService.get('currentUser');
    this.getTatamiStatus();
    this.tatamiService.getKataList().subscribe((response: any) => {
      if(response.result.success){
        this.kataList = response.result.data;
      }
    });
    
    
  }


  //============================================================ SALVATAGGIO PUNTEGGIO SINGOLO ==================================================

  checkSubmit(form: FormGroup, athleteId, type = null){

    if(form.value.total || form.value.total_time){
      this.tempFormRequest = {
        form: form,
        athleteId: athleteId,
        type: type
      }
      this.dangerModal.show();
    }else{
      this.saveScoresAndGetTotals(form, athleteId, type);
    }
    
  }

  editScores(){
    this.saveScoresAndGetTotals(this.tempFormRequest.form, this.tempFormRequest.athleteId, this.tempFormRequest.type);
    this.dangerModal.hide();
  }

  saveScoresAndGetTotals(form: FormGroup, athleteId, type = null){

    this.checkCategoryStatus(Constants.categoryStatus.DOING);
    switch (type) {
      case 'kiken':
        if(this.typeForm == 'KIA'){
          form = this.prevalueKata(form, 0);
        }else if(this.typeForm == 'PER'){
          form = this.prevaluePercorso(form);
        }else{
          form = this.prevalueBambini(form, 0);
        }
        break;
      case 'min_score':
        if(this.typeForm == 'KIA'){
          form = this.prevalueKata(form, 5.0);
        }else if(this.typeForm == 'PER'){
          form = this.prevaluePercorso(form);
        }else{
          form = this.prevalueBambini(form, 5.0);
        }
        break;
    
      default:
        break;
    }
    if(form.invalid && type == null){
      console.log('INVALID');
    }else{
      form.value.category = this.typeForm;
      this.tatamiService.saveScoresAndGetTotals(form.value).subscribe((response:any) => {
        if(response.result.success){
          this.getAtlheteList(form.value.athlete_inscription_id, athleteId);
        }
      })

    }
  }
  
  checkCategoryStatus(status){

    if(this.selectedCategory.status == status)
    return;

    if(this.selectedCategory.status == Constants.categoryStatus.TODO && status == Constants.categoryStatus.OPEN ||
      this.selectedCategory.status == Constants.categoryStatus.OPEN && status == Constants.categoryStatus.DOING){
        this.selectedCategory.status = status;
    
        let request = {
          categorycode_id: this.selectedCategory.categorycode_id,
          id: this.selectedCategory.id,
          status: status,
          user_id: this.selectedCategory.user_id
        }
        
        this.tatamiService.updateCategoryStatus(request).subscribe((response: any) => {
    
        })
      }
    
  }

  closeCategory(){

      this.selectedCategory.status = Constants.categoryStatus.CLOSED;
      this.athleteList = [];
      this.kumiteAthleteList = [];
      jQuery('#minimal').hide();
      jQuery('#girone-2').hide();
      jQuery('#girone-3').hide();
      let request = {
        categorycode_id: this.selectedCategory.categorycode_id,
        id: this.selectedCategory.id,
        status: Constants.categoryStatus.CLOSED,
        user_id: this.selectedCategory.user_id
      }
      this.tatamiService.updateCategoryStatus(request).subscribe((response: any) => {

      })
  }

  prevalueBambini(form, value){
    form.value.kata_id = null;
    form.value.referee_1 = value;
    form.value.referee_2 = value;
    form.value.referee_3 = value;
    form.value.penalty = 0;
    return form;
  }
  prevalueKata(form, value){
    form.value.kata_id = null;
    form.value.referee_1 = value;
    form.value.referee_2 = value;
    form.value.referee_3 = value;
    form.value.referee_4 = value;
    form.value.referee_5 = value;
    return form;
  }
  prevaluePercorso(form){
    form.value.minutes = 0;
    form.value.seconds = 0;
    form.value.milliseconds = 0;
    form.value.penalty = 0;
    return form;
  }

  // ================================================================ ELABORAZIONE PUNTEGGI CATEGORIA ==========================================

  elaboraPunteggi(nprova){
    console.log(this.selectedCategory);

    let request = {
      category: this.selectedCategory.categorycode.id,
      grado: this.selectedCategory.categorycode.grado,
      nprova: nprova
    };

    let allScore = true;
    this.athleteList.forEach(element => {
      if(element.scores.total == null && element.scores.total_time_seconds == null && element.scores.type != 'CL'){
        allScore = false;
      }
    });

    if(allScore){
      this.tatamiService.elaboraPunteggi(request).subscribe((response: any) => {
        if(response.result.success){
          this.getAtlheteList();
        }
      });
    }else{
      this.message = 'Non tutti gli atleti hanno un punteggio valido';
      this.doubleKataModal.show();
    }
    
  }

  // ------------------------------------------------ KATA CONTROLS ----------------------------------------------------------

  getPreviousMatch(athlete, type){

    if(this.previousMatches[0] && 
      this.previousMatches[0].athlete_inscription_id == athlete.athlete_inscription_id &&
      this.previousMatches[0].categorycode_id == this.selectedCategory.categorycode.id){
        this.matchModal.show();

      }else{
        let request = {
          athlete_inscription_id: athlete.athlete_inscription_id,
          categorycode_id: this.selectedCategory.categorycode.id
        }
    
        this.tatamiService.getPreviousMatch(request).subscribe((response:any) => {
          if(response.result.success){
            this.previousMatches = response.result.data;
            this.previousMatches.pop();
            this.matchModal.show();
          }
        })
      }
    

  }

  // ---------------------------------------------- OPEN MONITOR INFO ------------------------------------------------------
  displayAthlete(athlete){

    this.athleteList.forEach(element => {
      element.show = false;
      if(element.id == athlete.id){
        element.show = true;
      }
    });
    console.log(this.athleteList);

    if(this.typeForm != 'PER'){
      let athleteToDisplay = {
        athlete: athlete.athlete,
        athlete_id: athlete.athlete_id,
        categorycode: athlete.categorycode,
        categorycode_id: athlete.categorycode_id,
        cintura: athlete.cintura,
        competition_id: athlete.competition_id,
        modificato: athlete.modificato,
        id: athlete.id,
        n_iscrizione: athlete.n_iscrizione,
        scores: athlete.scores
      }
  
      this.openInfo(athleteToDisplay, 'AT');
    }

    
  }

  openInfo(athlete, type = null){

    if(type == 'CL'){
      if(athlete.length > 4){
        let tempAthlete = [];

        for (let index = 0; index < 4; index++) {
          tempAthlete.push(athlete[index]);
        }
        this.localStorageService.set('classifica', tempAthlete);
      }else{
        this.localStorageService.set('classifica', athlete);
      }
      this.localStorageService.set('type', type);
      this.localStorageService.set(Constants.monitor.ATHLETE_1, null);
      this.localStorageService.set(Constants.monitor.KUMITE, null);
    }else if(type == 'AT'){
      this.localStorageService.set(Constants.monitor.ATHLETE_1, athlete);
      this.localStorageService.set('classifica', null);
      this.localStorageService.set(Constants.monitor.KUMITE, null);
    }else if(type == 'KUMITE'){
      this.localStorageService.set(Constants.monitor.ATHLETE_1, null);
      this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
      this.localStorageService.set('classifica', null);
    }else{
      this.localStorageService.set(Constants.monitor.ATHLETE_1, null);
      this.localStorageService.set(Constants.monitor.KUMITE, null);
      this.localStorageService.set('classifica', null);
    }      
      
      //window.open("http://" + document.location.hostname + "/csenveneto/displayinfo", "http://" + document.location.hostname + "/csenveneto/displayinfo", "toolbar=no");

      window.open(environment.DISPLYINFO_URL, environment.DISPLYINFO_URL, "toolbar=no");

    
    
  }

  // ------------------------------------------------ UTILS METHODS --------------------------------------------------------


  // ELENCO CATEGORIE ASSEGNATE AL TATAMI
  getTatamiStatus(){
    let request = {
      id: this.currentUser.id
    };
    this.tatamiService.getTatamiStatus(request).subscribe((response: any) => {
      this.tatamiStatus = response.result.data;
    });
  }

  // ELENCO ATLETI NELLA CATEGORIA SELEZIONATA
  onSelect(category){
    
    this.selectedCategory = category;
    console.log(this.selectedCategory)

    this.typeForm = category.categorycode.id.substring(0, 3);
    this.labelCategory = `${category.categorycode.specialita} - ${category.categorycode.categoria} ${category.categorycode.grado} - ${category.categorycode.sesso} ${category.categorycode.cat_peso}`
    this.athleteList = [];
    this.kumiteAthleteList = [];
    this.kumiteMatches = [];

    this.checkCategoryStatus(Constants.categoryStatus.OPEN);
    
    this.getAtlheteList();
  }

  sortMarkets(array, sortArray){
    return [...array].sort(
      (a, b) => sortArray.indexOf(a.position) - sortArray.indexOf(b.position)
    )
  }

  getAtlheteList(athleteId = null, athleteId_info = null){

    this.tatamiService.getAthleteList({categorycode_id: this.selectedCategory.categorycode_id, readonly: false}).subscribe((response: any) => {

      //==================================================== KUMITE ==========================================================
  
      if(this.typeForm == Constants.prova.KUMITE || this.typeForm == Constants.prova.KUMITE_U12){

        this.kumiteMatches = []; //coppie primo giro di incontri
        this.kumiteAthleteList = []; //lista atleti del kumite

        //imposto iltempo in base a categoria

        if(this.selectedCategory.categorycode.categoria == 'RAGAZZI'){
          this.countdown = 60;
        }else if(this.selectedCategory.categorycode.categoria == 'ESORDIENTI'){
          this.countdown = 90;
        }else{
          this.countdown =120;
        }        
        this.kumiteAthleteList = response.result.data;
        
        //SE GIRONE ALL'ITALIANA 3 INCONTRI DA 2
        if(response.result.data.length == 3){

          
          this.firstMatch = [
            {name: this.kumiteAthleteList[0]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[0]['athlete']['nome'], id: this.kumiteAthleteList[0]['id'], round: 1},
            {name: this.kumiteAthleteList[1]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[1]['athlete']['nome'], id: this.kumiteAthleteList[1]['id'], round: 1}
          ];
          let firstResult = [null, null];
          if(this.kumiteAthleteList[0]['old_scores'] && this.kumiteAthleteList[1]['old_scores']){

            this.kumiteAthleteList[0]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[1].id){
                firstResult[0] = score.total;
              }
            });

            this.kumiteAthleteList[1]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[0].id){
                firstResult[1] = score.total;
              }
            });
          }
          this.secondMatch = [
            {name: this.kumiteAthleteList[0]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[0]['athlete']['nome'], id: this.kumiteAthleteList[0]['id'], round: 2},
            {name: this.kumiteAthleteList[2]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[2]['athlete']['nome'], id: this.kumiteAthleteList[2]['id'], round: 2}
          ];
          let secondResult = [null, null];
          if(this.kumiteAthleteList[0]['old_scores'] && this.kumiteAthleteList[2]['old_scores']){

            this.kumiteAthleteList[0]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[2].id){
                secondResult[0] = score.total;
              }
            });

            this.kumiteAthleteList[2]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[0].id){
                secondResult[1] = score.total;
              }
            });
          }
          this.thirdMatch = [
            {name: this.kumiteAthleteList[1]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[1]['athlete']['nome'], id: this.kumiteAthleteList[1]['id'], round: 3},
            {name: this.kumiteAthleteList[2]['athlete']['cognome'] + ' ' + this.kumiteAthleteList[2]['athlete']['nome'], id: this.kumiteAthleteList[2]['id'], round: 3}
          ];  
          
          let thirdResult = [null, null];
          if(this.kumiteAthleteList[1]['old_scores'] && this.kumiteAthleteList[2]['old_scores']){

            this.kumiteAthleteList[1]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[2].id){
                thirdResult[0] = score.total;
              }
            });

            this.kumiteAthleteList[2]['old_scores'].forEach(score => {
              if(score.opponentid == this.kumiteAthleteList[1].id){
                thirdResult[1] = score.total;
              }
            });
          }

          this.minimalData = {
              teams: [this.firstMatch],
              results: firstResult
          }

          this.secondGirone = {
            teams: [this.secondMatch],
            results: secondResult
          }

          this.thirdGirone = {
            teams: [this.thirdMatch],
            results: thirdResult
          }           
  
            
      function render_fn(container, data, score, state) {
        switch(state) {
          case "empty-bye":
            container.append("--")
            return;
          case "empty-tbd":
            container.append("Upcoming")
            return;
       
          case "entry-no-score":
          case "entry-default-win":
          case "entry-complete":
            container.append(data.name)
            return;
        }
      }
      const component = this;
  
      /* Edit function is called when team label is clicked */
      function edit_fn(container, data, doneCb) {
        component.openMatch(data);
        doneCb(data);
      }
  
  
  
        jQuery('#minimal').bracket({
              teamWidth: 250,
              scoreWidth: 45,
              matchMargin: 70,
              roundMargin: 70,
              init: this.minimalData,
              save: function(){},
              decorator:{edit:edit_fn, render: render_fn},
          });
          jQuery('#girone-2').show();

        jQuery('#girone-3').show();

          jQuery('#girone-2').bracket({
            teamWidth: 250,
            scoreWidth: 45,
            matchMargin: 70,
            roundMargin: 70,
            init: this.secondGirone,
            save: function(){},
            decorator:{edit:edit_fn, render: render_fn},
        });

        jQuery('#girone-3').bracket({
          teamWidth: 250,
          scoreWidth: 45,
          matchMargin: 70,
          roundMargin: 70,
          init: this.thirdGirone,
          save: function(){},
          decorator:{edit:edit_fn, render: render_fn},
      });

      

        }else{
          
          this.firstMatch = [];
          this.secondMatch = [];
          this.thirdMatch = [];
          let position = 1;
          let dataset:Tabset = null;

          switch (true) {
            case (this.kumiteAthleteList.length == 2):
              dataset = Dataset.dataset2;
              break;
            case (this.kumiteAthleteList.length == 4):
              dataset = Dataset.dataset4;
              break;
            case (this.kumiteAthleteList.length <= 8):
              dataset = Dataset.dataset8;
              break;
            case (this.kumiteAthleteList.length <= 16):
              dataset = Dataset.dataset16;
              break;
            case (this.kumiteAthleteList.length <= 32):
              dataset = Dataset.dataset32;
              break;
            default:
              console.log('ERROREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE');
              break;
          }

          while (position <= dataset.number) {
            let index = position-1;
            if(this.kumiteAthleteList[index]){
              this.kumiteMatches.push({
                position: position, 
                id: this.kumiteAthleteList[index].id, 
                name: this.kumiteAthleteList[index].athlete.cognome + ' ' + this.kumiteAthleteList[index].athlete.nome, 
                club:this.kumiteAthleteList[index].athlete.club.nome_societa
              })
            }else{
              this.kumiteMatches.push({
                position: position, 
                id: null, 
                name: null, 
                club: null
              })
            }
            
            position++;
          }

          this.kumiteMatches = this.sortMarkets(this.kumiteMatches, dataset.positions);
  
          const datasetTemp: Dataset = {
            title: 'Kumite',
            type: 'single_elimination',
            roster: this.kumiteMatches,
          };
          console.log(this.kumiteMatches);

          
          process(datasetTemp).then((data) => 
          {
  
            let teams = [];
            let results = [];
            let opponent1 = null;
            let opponent2 = null;
            results.push([]);
            //console.log(data);
            data.matches.forEach(match => {
  
              
              if(match.round_id == 0){
  
                opponent1 = match.opponent1 ? {name: '', id: match.opponent1.id} : null;
                opponent2 = match.opponent2 ? {name: '', id: match.opponent2.id} : null;
  
                let firstAthlete = null;
                let secondAthlete = null;
  
                let result = [null,null];
  
                let request = [];
    
                data.participants.forEach(participant => {
                  if(opponent1 && opponent1.id == participant.id){
                    opponent1.name = participant.name;
                    firstAthlete = this.kumiteAthleteList.find(x => x.id == opponent1.id);
                   console.log(opponent1);
                   console.log(opponent2);
                   console.log('----------------------- FIRST ATHLETE -----------------------------------------');
                   console.log(firstAthlete);
                    if(opponent2){
                      console.log('entraaaaa');
                      
                      
                      if(firstAthlete.old_scores && firstAthlete.old_scores.length > 0){
                        result[0] = firstAthlete.old_scores[0]['total'];
                      }else{
                        firstAthlete.scores.opponentid = opponent2.id;
                        firstAthlete.scores.color = 'AKA';
                        request.push(firstAthlete.scores);
                      }
                    }
                    
                    
                  }
                  if(opponent2 && opponent2.id == participant.id){
                    opponent2.name = participant.name;
                    secondAthlete = this.kumiteAthleteList.find(x => x.id == opponent2.id);
                    
                    console.log('----------------------- SECOND ATHLETE -----------------------------------------');
                    console.log(secondAthlete);
                    
                    if(secondAthlete.old_scores && secondAthlete.old_scores.length > 0){
                      result[1] = secondAthlete.old_scores[0]['total'];                    
                    }else{
                      secondAthlete.scores.opponentid = opponent1.id;
                      secondAthlete.scores.color = 'AO';
                      request.push(secondAthlete.scores);
                    }
                  }              
                    
  
                });
  
                if(request.length > 0){
                  this.tatamiService.matchAthletes(request).subscribe((response) => {
  
                  });
                }
  
  
                teams.push([opponent1, opponent2]);
                
                results[0].push(result);
              }
              
            });

          
            let secondRound = [];
            let thirdRound = [];
            let fourthRound = [];
  
            results[0].forEach((match, indexMatch) => {
  
              console.log(match);
              console.log(teams);
  
              if(match[0] == null && match[1]== null){
                if(teams[indexMatch][0] != null && teams[indexMatch][1] == null){
                  secondRound.push(teams[indexMatch][0]);
                }else if(teams[indexMatch][1] != null && teams[indexMatch][0] == null){
                  secondRound.push(teams[indexMatch][1]);
                }else if (teams[indexMatch][0] != null && teams[indexMatch][1] != null){
                  secondRound.push(null);
                }
              }
                
              if(match[0] != null && match[1] != null){
                if(match[0] > match[1]){
                  secondRound.push(teams[indexMatch][0]);
                }else if(match[0] < match[1]){
                  secondRound.push(teams[indexMatch][1]);
                }
              }
            });
                  
            console.log(secondRound);

            //------------------------------ SECONDO ROUND -----------------------------------------------------------------------------------------
  
  
            if(secondRound[0] && secondRound[1]){
  
              console.log('----------------------------- ENTRA 2 ROUND 1 ---------------------------------------------------------------');
              let firstAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[0]['id']);
              let secondAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[1]['id']);
  
              console.log(firstAthlete);
              console.log(secondAthlete);
  
              let result = [null,null];
              //devo ancora giocare il secondo round
              if(firstAthlete.old_scores && secondAthlete.old_scores){
                
                  firstAthlete.old_scores.forEach(score => {
                    if(score.opponentid == secondAthlete.id){
                      result[0] = score.total;
                    }
                  });
  
                  secondAthlete.old_scores.forEach(score => {
                    if(score.opponentid == firstAthlete.id){
                      result[1] = score.total;
                    }
                  }); 
                  
                //results[1][0] = result;
               
              }
              if(result[0] != null && result[1] != null){
                if(result[0] > result[1]){
                  thirdRound.push(firstAthlete);
                }else if(result[0] < result[1]){
                  thirdRound.push(secondAthlete);
                }
              }else{
                firstAthlete.scores.opponentid = secondAthlete.id;
                firstAthlete.scores.color = 'AKA';
                secondAthlete.scores.opponentid = firstAthlete.id;
                secondAthlete.scores.color = 'AO';
  
                let request = [];
                request.push(firstAthlete.scores);
                request.push(secondAthlete.scores);
                this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
              }
              

              if(results[1]){
                results[1][0] = result;
              }else {
                results.push([result]);
              }
              console.log(results);
              
            }
  
            if(secondRound[2] && secondRound[3]){
  
              console.log('----------------------------- ENTRA 2 ROUND 2 ---------------------------------------------------------------');

              let firstAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[2]['id']);
              let secondAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[3]['id']);
  
              let result = [null,null];
              //devo ancora giocare il secondo round
              
              if(firstAthlete.old_scores && secondAthlete.old_scores){
                
                firstAthlete.old_scores.forEach(score => {
                  if(score.opponentid == secondAthlete.id){
                    result[0] = score.total;
                  }
                });

                secondAthlete.old_scores.forEach(score => {
                  if(score.opponentid == firstAthlete.id){
                    result[1] = score.total;
                  }
                }); 
                
              //results[1][0] = result;
             
              }
              if(result[0] != null && result[1] != null){

                console.log('entraaaaaa');
                if(result[0] > result[1]){
                  thirdRound.push(firstAthlete);
                }else if(result[0] < result[1]){
                  thirdRound.push(secondAthlete);
                }
              }else{
                firstAthlete.scores.opponentid = secondAthlete.id;
                firstAthlete.scores.color = 'AKA';
                secondAthlete.scores.opponentid = firstAthlete.id;
                secondAthlete.scores.color = 'AO';

                let request = [];
                request.push(firstAthlete.scores);
                request.push(secondAthlete.scores);
                this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
              }

              //results[1][1] = result;
              if(results[1]){

                if(results[1].length >= 2){
                  results[1][1] = result;
                }else{
                  if(results[1][0]){
                    results[1].push(result);
                  }else{
                    results[1].push([null, null]);
                    results[1].push(result);
                  }                    
                }
                
              }else {
                results.push([[null, null]]);
                results[1].push([null, null]);
                results[1].push(result);
              }
              console.log(results);

            }

            if(secondRound[4] && secondRound[5]){
    

              console.log('----------------------------- ENTRA 2 ROUND 3 ---------------------------------------------------------------');
              let firstAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[4]['id']);
              let secondAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[5]['id']);

              console.log(firstAthlete);
              console.log(secondAthlete);

              let result = [null,null];

              if(firstAthlete.old_scores && secondAthlete.old_scores){
                
                firstAthlete.old_scores.forEach(score => {
                  if(score.opponentid == secondAthlete.id){
                    result[0] = score.total;
                  }
                });

                secondAthlete.old_scores.forEach(score => {
                  if(score.opponentid == firstAthlete.id){
                    result[1] = score.total;
                  }
                }); 
                
              //results[1][0] = result;
             
              }
              if(result[0] != null && result[1] != null){
                if(result[0] > result[1]){
                  thirdRound.push(firstAthlete);
                }else if(result[0] < result[1]){
                  thirdRound.push(secondAthlete);
                }
              }else{
                firstAthlete.scores.opponentid = secondAthlete.id;
                firstAthlete.scores.color = 'AKA';
                secondAthlete.scores.opponentid = firstAthlete.id;
                secondAthlete.scores.color = 'AO';

                let request = [];
                request.push(firstAthlete.scores);
                request.push(secondAthlete.scores);
                this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
              }

               //results[1][2] = result;
               if(results[1]){
                if(results[1].length >= 3){
                  results[1][2] = result;
                }else{
                  if(results[1][1] == undefined){
                    results[1].push([null, null]);
                    results[1].push(result);
                  }else{
                    results[1].push(result);
                  }
                }
                
              }else {
                results.push([[null, null]]);
                results[1].push([null, null]);
                results[1].push([null, null]);
                results[1].push(result);
              }
              
            }

            if(secondRound[6] && secondRound[7]){

              console.log('----------------------------- ENTRA 2 ROUND 4 ---------------------------------------------------------------');

              let firstAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[6]['id']);
              let secondAthlete = this.kumiteAthleteList.find(x => x.id == secondRound[7]['id']);

              console.log(firstAthlete);
                console.log(secondAthlete);

              let result = [null,null];
              //devo ancora giocare il secondo round
              
              if(firstAthlete.old_scores && secondAthlete.old_scores){
                
                firstAthlete.old_scores.forEach(score => {
                  if(score.opponentid == secondAthlete.id){
                    result[0] = score.total;
                  }
                });

                secondAthlete.old_scores.forEach(score => {
                  if(score.opponentid == firstAthlete.id){
                    result[1] = score.total;
                  }
                }); 
                
              //results[1][0] = result;
             
              }
              if(result[0] != null && result[1] != null){
                if(result[0] > result[1]){
                  thirdRound.push(firstAthlete);
                }else if(result[0] < result[1]){
                  thirdRound.push(secondAthlete);
                }
              }else{
                firstAthlete.scores.opponentid = secondAthlete.id;
                firstAthlete.scores.color = 'AKA';
                secondAthlete.scores.opponentid = firstAthlete.id;
                secondAthlete.scores.color = 'AO';

                let request = [];
                request.push(firstAthlete.scores);
                request.push(secondAthlete.scores);
                this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
              }

            //results[1][3] = result;
            if(results[1]){
              if(results[1].length >= 4){
                results[1][3] = result;
              }else{
                if(results[1][1] == undefined){
                  results[1].push([null, null]);
                  results[1].push([null, null]);
                  results[1].push(result);
                } else if(results[1][2] == undefined){
                  results[1].push([null, null]);
                  results[1].push(result);
                }else{
                  results[1].push(result);
                }
              }
              
            }else {
              results.push([[null, null]]);
                results[1].push([null, null]);
                results[1].push([null, null]);
                results[1].push([null, null]);
                results[1].push(result);
            }
          }
  
          // ------------------------------------------- TERZO ROUND --------------------------------------------------------------------------------

          if(thirdRound[0] && thirdRound[1]){

            console.log('----------------------------- ENTRA 3 ROUND 1 ---------------------------------------------------------------');
  
            let firstAthlete = this.kumiteAthleteList.find(x => x.id == thirdRound[0]['id']);
            let secondAthlete = this.kumiteAthleteList.find(x => x.id == thirdRound[1]['id']);

            console.log(firstAthlete);
            console.log(secondAthlete);

            let result = [null,null];
            
            if(firstAthlete.old_scores && secondAthlete.old_scores){
              
                firstAthlete.old_scores.forEach(score => {
                  if(score.opponentid == secondAthlete.id){
                    result[0] = score.total;
                  }
                });

                secondAthlete.old_scores.forEach(score => {
                  if(score.opponentid == firstAthlete.id){
                    result[1] = score.total;
                  }
                });

            }

            if(result[0] != null && result[1] != null){
              if(result[0] > result[1]){
                fourthRound.push(firstAthlete);
              }else if(result[0] < result[1]){
                fourthRound.push(secondAthlete);
              }
            }else{
              firstAthlete.scores.opponentid = secondAthlete.id;
              firstAthlete.scores.color = 'AKA';
              secondAthlete.scores.opponentid = firstAthlete.id;
              secondAthlete.scores.color = 'AO';

              let request = [];
              request.push(firstAthlete.scores);
              request.push(secondAthlete.scores);
              this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
            }

            //results[2][0] = result;
            if(results[2]){
              results[2][0] = result;
            }else if (results[1]){
              results.push([result]);
            }

            console.log(results);
            
          }

          if(thirdRound[2] && thirdRound[3]){

            console.log('----------------------------- ENTRA 3 ROUND 2 ---------------------------------------------------------------');

            let firstAthlete = this.kumiteAthleteList.find(x => x.id == thirdRound[2]['id']);
            let secondAthlete = this.kumiteAthleteList.find(x => x.id == thirdRound[3]['id']);

            console.log(firstAthlete);
            console.log(secondAthlete);

            let result = [null,null];

            if(firstAthlete.old_scores && secondAthlete.old_scores){
              
              firstAthlete.old_scores.forEach(score => {
                if(score.opponentid == secondAthlete.id){
                  result[0] = score.total;
                }
              });

              secondAthlete.old_scores.forEach(score => {
                if(score.opponentid == firstAthlete.id){
                  result[1] = score.total;
                }
              });

            }

            if(result[0] != null && result[1] != null){
              if(result[0] > result[1]){
                fourthRound.push(firstAthlete);
              }else if(result[0] < result[1]){
                fourthRound.push(secondAthlete);
              }
            }else{
              firstAthlete.scores.opponentid = secondAthlete.id;
              firstAthlete.scores.color = 'AKA';
              secondAthlete.scores.opponentid = firstAthlete.id;
              secondAthlete.scores.color = 'AO';

              let request = [];
              request.push(firstAthlete.scores);
              request.push(secondAthlete.scores);
              this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
            }

          //results[2][1] = result;
          if(results[2]){
            if(results[2][0]){
              results[2].push(result);
            }else{
              results[2].push([null, null]);
              results[2].push(result);
            }
            
          }else {
            results.push([[null, null]]);
            results[2].push([null, null]);
            results[2].push(result);
          }

        }

        // ----------------------------------------------------- QUARTO ROUND --------------------------------------------------------------

          if(fourthRound[0] && fourthRound[1]){
            console.log('----------------------------- ENTRA 4 ---------------------------------------------------------------');
            let firstAthlete = this.kumiteAthleteList.find(x => x.id == fourthRound[0]['id']);
            let secondAthlete = this.kumiteAthleteList.find(x => x.id == fourthRound[1]['id']);
  
            let result = [null,null];
            
            if(firstAthlete.old_scores && secondAthlete.old_scores){
  
                firstAthlete.old_scores.forEach(score => {
                  if(score.opponentid == secondAthlete.id){
                    result[0] = score.total;
                  }
                });
  
                secondAthlete.old_scores.forEach(score => {
                  if(score.opponentid == firstAthlete.id){
                    result[1] = score.total;
  
                  }
                });
            }

            if(result[0] != null && result[1] != null){

              //results[2][0] = result;
              if(results[3]){
                results[3][0] = result;
              }else if(results[2]){
                results.push([result]);
              }
            }else{
              firstAthlete.scores.opponentid = secondAthlete.id;
              firstAthlete.scores.color = 'AKA';
              secondAthlete.scores.opponentid = firstAthlete.id;
              secondAthlete.scores.color = 'AO';
  
              let request = [];
              request.push(firstAthlete.scores);
              request.push(secondAthlete.scores);
              this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
            }

            
          }  

            this.minimalData = {
              teams: teams,
              results: results
            }
            
  
            
      function render_fn(container, data, score, state) {
        switch(state) {
          case "empty-bye":
            container.append("--")
            return;
          case "empty-tbd":
            container.append("Upcoming")
            return;
       
          case "entry-no-score":
          case "entry-default-win":
          case "entry-complete":
            container.append(data.name)
            return;
        }
      }
      const component = this;
  
      // Edit function is called when team label is clicked
      function edit_fn(container, data, doneCb) {
        component.openMatch(data);
        doneCb(data);
      }
  
  
  
        jQuery('#minimal').bracket({
              teamWidth: 200,
              scoreWidth: 45,
              matchMargin: 70,
              roundMargin: 70,
              init: this.minimalData,
              save: function(){},
              decorator:{edit:edit_fn, render: render_fn},
          })
          jQuery('#girone-2').hide();

        jQuery('#girone-3').hide();
    
          });
        }
        
        //=============================================================== COMBINATA E KATA =====================================================================
      }else{

        this.athleteList = response.result.data;

        this.athleteList.forEach(element => {
          element.show = false;

          if(athleteId_info && element.id == athleteId_info){

               this.openInfo(element, 'AT');
               element.show = true;
            }
            
        });

        
  
        if(this.athleteList[0].scores.type=='CL' || this.athleteList[0].scores.cl_position && this.athleteList.length<=4){

          for( var i = 0; i < this.athleteList.length; i++){ 
    
            if ( this.athleteList[i].scores.cl_position == -1) { 
        
              this.athleteList.splice(i, 1); 
              i--; 
            }
        
        }


          this.athleteList.sort(function (a, b) {
            return a.scores.cl_position - b.scores.cl_position;
          });
  
          if(this.athleteList[0].scores.type=='CL'){
            this.openInfo(this.athleteList, 'CL');
          }
          
        }else if (this.athleteList[0].scores.cl_position){

          this.athleteList.sort(function (a, b) {
            return b.scores.cl_position - a.scores.cl_position;
          });
        }
        this.athleteList.forEach(element => {

  
          switch (this.typeForm) {
            case Constants.prova.KATA_ADULTI:
            case Constants.prova.KATA_SQUADRE:
              if(element.scores.total){
  
                let max = false;
                let min = false;
                
  
                //cerco punteggi eliminati
                if(element.scores.referee_1 == element.scores.min){
                  element.scores.min_referee = 1;
                  min = true;
                }else if (element.scores.referee_1 == element.scores.max){
                  element.scores.max_referee = 1;
                  max= true;
                }
                if(element.scores.referee_2 == element.scores.min && !min){
                  element.scores.min_referee = 2;
                  min = true;
                }else if (element.scores.referee_2 == element.scores.max && !max){
                  element.scores.max_referee = 2;
                  max= true;
                }
                if(element.scores.referee_3 == element.scores.min && !min){
                  element.scores.min_referee = 3;
                  min = true;
                }else if (element.scores.referee_3 == element.scores.max && !max){
                  element.scores.max_referee = 3;
                  max= true;
                }
                if(element.scores.referee_4 == element.scores.min && !min){
                  element.scores.min_referee = 4;
                  min = true;
                }else if (element.scores.referee_4 == element.scores.max && !max){
                  element.scores.max_referee = 4;
                  max= true;
                }
                if(element.scores.referee_5 == element.scores.min && !min){
                  element.scores.min_referee = 5;
                  min = true;
                }else if (element.scores.referee_5 == element.scores.max && !max){
                  element.scores.max_referee = 5;
                  max= true;
                }
                
              }
  
              element.scoreForm = this.buildKataAdutiForm(element);            
              break;
  
            case Constants.prova.KATA_BAMBINI:
              element.scoreForm = this.buildKataBambiniForm(element);
              break;
            case Constants.prova.PERCORSO:
              element.scoreForm = this.buildPercorsoForm(element);
              break;
            case Constants.prova.PALLONCINO:
              element.scoreForm = this.buildPalloncinoForm(element);
              break;
          
            default:
              break;
          }
  
        });
      }
      
    });
  }


  resetTimer(){
    if(this.selectedCategory.categorycode.categoria == 'RAGAZZI'){
      this.countdown = 60;
    }else if(this.selectedCategory.categorycode.categoria == 'ESORDIENTI'){
      this.countdown = 90;
    }else{
      this.countdown =120;
    }
    this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(Constants.monitor.ACTION, 'MODIFY');
  }

  modifyTimer(operation){
    if(operation == 'PLUS'){
      this.countdown++;
    }else{
      this.countdown--;
    }
    this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(Constants.monitor.ACTION, 'MODIFY');
  }

  // Popolo form KATA ADULTI
  buildKataAdutiForm(element){
    const kataAdultiGroup = new FormGroup({
      id: new FormControl(element.scores.id),
      name: new FormControl({value: element.athlete.cognome + ' ' + element.athlete.nome, disabled: true}, Validators.required),
      athlete_inscription_id: new FormControl(element.id),
      user_id: new FormControl(this.currentUser.id),
      kata_id: new FormControl(element.scores.kata_id?? ''),
      referee_1: new FormControl(element.scores.referee_1?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_2: new FormControl(element.scores.referee_2?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_3: new FormControl(element.scores.referee_3?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_4: new FormControl(element.scores.referee_4?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_5: new FormControl(element.scores.referee_5?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      total: new FormControl(element.scores.total?? '')
    });

    return kataAdultiGroup;
    //this.scoreForm.push(kataAdultiGroup);

  }

  evenNumberValidator(): ValidatorFn{
    return (control:AbstractControl) : ValidationErrors | null => {

      const value = control.value;

      if (!value) {
          return null;
      }

      return value*10 % 2 == 0 ? null : {notInRange:true};
  }
  }

    // Popolo form KATA BAMBINI
  buildKataBambiniForm(element){
    
    const kataBambiniGroup = new FormGroup({
      id: new FormControl(element.scores.id),
      name: new FormControl({value: element.athlete.cognome + ' ' + element.athlete.nome, disabled: true}),
      athlete_inscription_id: new FormControl(element.id),
      user_id: new FormControl(this.currentUser.id),
      kata_id: new FormControl(element.scores.kata_id?? ''),
      referee_1: new FormControl(element.scores.referee_1?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_2: new FormControl(element.scores.referee_2?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_3: new FormControl(element.scores.referee_3?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      total: new FormControl(element.scores.total?? '')
    });

    return kataBambiniGroup;
    //this.scoreForm.push(kataBambiniGroup);

  }

      // Popolo form PERCORSO
  buildPercorsoForm(element){

    const percorsoGroup = new FormGroup({
      id: new FormControl(element.scores.id),
      name: new FormControl({value: element.athlete.cognome + ' ' + element.athlete.nome, disabled: true}),
      athlete_inscription_id: new FormControl(element.id),
      user_id: new FormControl(this.currentUser.id),
      minutes: new FormControl(element.scores.minutes?? ''),
      seconds: new FormControl(element.scores.seconds?? ''),
      milliseconds: new FormControl(element.scores.milliseconds?? ''),
      penalty: new FormControl(element.scores.penalty?? ''),
      total_time: new FormControl(element.scores.total_time?? '')
    });

    return percorsoGroup;
    //this.scoreForm.push(percorsoGroup);
  }

    // Popolo form PALLONCINO
  buildPalloncinoForm(element){

    const palloncinoGroup = new FormGroup({
      id: new FormControl(element.scores.id),
      name: new FormControl({value: element.athlete.cognome + ' ' + element.athlete.nome, disabled: true}),
      athlete_inscription_id: new FormControl(element.id),
      user_id: new FormControl(this.currentUser.id),
      referee_1: new FormControl(element.scores.referee_1?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_2: new FormControl(element.scores.referee_2?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_3: new FormControl(element.scores.referee_3?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
        total_without_penalties: new FormControl(element.scores.total_without_penalties?? ''),
      penalty: new FormControl(element.scores.penalty?? ''),
      total: new FormControl(element.scores.total?? '')
    });

    return palloncinoGroup;
    
  }

// ========================================================  KUMITE =======================================================================

  openMatch(data){

    this.currentMatch = null;
    let aka = null;
    let ao = null;
    if(this.kumiteAthleteList.length == 3){
      
      if(data.round == 1){
        aka = this.kumiteAthleteList.find(x => x ? x.id == this.firstMatch[0].id : null);
        ao = this.kumiteAthleteList.find(x => x ? x.id == this.firstMatch[1].id : null);
      
      }else if (data.round == 2){
        aka = this.kumiteAthleteList.find(x => x ? x.id == this.secondMatch[0].id : null);
        ao = this.kumiteAthleteList.find(x => x ? x.id == this.secondMatch[1].id : null);
      }else{
        aka = this.kumiteAthleteList.find(x => x ? x.id == this.thirdMatch[0].id : null);
        ao = this.kumiteAthleteList.find(x => x ? x.id == this.thirdMatch[1].id : null);
      }

      aka.scores.color = 'AKA';
      aka.scores.opponentid = ao.id;
      ao.scores.color = 'AO';
      ao.scores.opponentid = aka.id;

      let request = [];
      request.push(aka.scores);
      request.push(ao.scores);
      this.tatamiService.matchAthletes(request).subscribe((response: any) => {});
  

    }else{

      console.log(this.kumiteAthleteList);
      let selectedAthlete = this.kumiteAthleteList.find(x => x ? x.id == data.id : null);
      let secondAthlete = this.kumiteAthleteList.find(x => x ? x.id == selectedAthlete.scores.opponentid : null);
      console.log(selectedAthlete);
      console.log(secondAthlete);

      aka = selectedAthlete.scores.color == 'AKA' ? selectedAthlete : secondAthlete;
      ao = secondAthlete.scores.color == 'AO' ? secondAthlete : selectedAthlete;  

    }

    this.currentMatch = {
      aka: aka,
      ao:ao
    }
    

    console.log(this.currentMatch);
    this.kumiteModal.show();


    this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);
    this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(Constants.monitor.ACTION, '');
    this.openInfo(null, 'KUMITE');
  }

  closeMatch(){
    //TODO: salavare punteggi e aggiornare griglia richiamando getAtleti
    this.kumiteModal.hide();
  }

  editScoreAka(score, action){

    switch (score) {
      case 1:
        if(action == 'ADD'){
          this.currentMatch.aka.scores.yuko++;
        }else if (action == 'REMOVE' && this.currentMatch.aka.scores.total > 0){
          this.currentMatch.aka.scores.yuko--;
        }
        break;
      case 2:
        if(action == 'ADD'){
          this.currentMatch.aka.scores.wazaari++;
        }else if (action == 'REMOVE' && this.currentMatch.aka.scores.total > 1){
          this.currentMatch.aka.scores.wazaari--;
        }
        break;
      case 3:
        if(action == 'ADD'){
          this.currentMatch.aka.scores.ippon++;
        }else if (action == 'REMOVE' && this.currentMatch.aka.scores.total > 2){
          this.currentMatch.aka.scores.ippon--;
        }
        break;
      default:
        break;
    }

    this.currentMatch.aka.scores.total = this.currentMatch.aka.scores.yuko + this.currentMatch.aka.scores.ippon*3 + this.currentMatch.aka.scores.wazaari*2;
    this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);

    if(this.currentMatch.aka.scores.total > this.currentMatch.ao.scores.total && this.currentMatch.aka.scores.total - this.currentMatch.ao.scores.total >= 8){
      this.winMatch('AKA');
    }
  }

  editScoreAo(score, action){
    switch (score) {
      case 1:
        if(action == 'ADD'){
          this.currentMatch.ao.scores.yuko++;
        }else if (action == 'REMOVE' && this.currentMatch.ao.scores.total > 0){
          this.currentMatch.ao.scores.yuko--;
        }
        break;
      case 2:
        if(action == 'ADD'){
          this.currentMatch.ao.scores.wazaari++;
        }else if (action == 'REMOVE' && this.currentMatch.ao.scores.total > 1){
          this.currentMatch.ao.scores.wazaari--;
        }
        break;
      case 3:
        if(action == 'ADD'){
          this.currentMatch.ao.scores.ippon++;
        }else if (action == 'REMOVE' && this.currentMatch.ao.scores.total > 2){
          this.currentMatch.ao.scores.ippon--;
        }
        break;
      default:
        break;
    }

    this.currentMatch.ao.scores.total = this.currentMatch.ao.scores.yuko + this.currentMatch.ao.scores.ippon*3 + this.currentMatch.ao.scores.wazaari*2;
    this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);

    if(this.currentMatch.ao.scores.total > this.currentMatch.aka.scores.total && this.currentMatch.ao.scores.total - this.currentMatch.aka.scores.total >= 8){
      this.winMatch('AO');
    }
    
  }

  winMatch(color){
    if(color == 'AKA'){
      this.currentMatch.aka.scores.win = 1;
      this.currentMatch.ao.scores.win = 0;
      
    }else{
      this.currentMatch.aka.scores.win = 0;
      this.currentMatch.ao.scores.win = 1;
    }

    console.log(this.currentMatch);
    this.countdown = 0;
    this.localStorageService.set(Constants.monitor.ACTION, Constants.monitor.ACTION_PAUSE);
    this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);
    this.pauseTimer();
  }

  saveMatch(){

    if(this.currentMatch.aka.scores.win == 1 && this.currentMatch.aka.scores.total <= this.currentMatch.ao.scores.total){
      this.currentMatch.aka.scores.total = this.currentMatch.ao.scores.total +1;
    }
    if(this.currentMatch.ao.scores.win == 1 && this.currentMatch.aka.scores.total >= this.currentMatch.ao.scores.total){
      this.currentMatch.ao.scores.total = this.currentMatch.aka.scores.total +1;
    }
    console.log(this.currentMatch);
    this.checkCategoryStatus(Constants.categoryStatus.DOING);
    this.tatamiService.saveAthleteScores(this.currentMatch).subscribe((response: any) => {
      if(response.result.success){
        if(this.selectedCategory.categorycode.categoria == 'ESORDIENTI'){
          this.countdown = 90;
        }else{
          this.countdown =120;
        }
        this.getAtlheteList();
      }
    })
    this.kumiteModal.hide();
  }
  

  startTimer() {
    this.showStartButton = false;
    this.localStorageService.set(Constants.monitor.ACTION, Constants.monitor.ACTION_START);
    this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
    if(this.countdown > 0) {
      this.countdown--;
    }
    this.interval = setInterval(() => {
      if(this.countdown > 0) {
        this.countdown--;
      } else {
        if(this.currentMatch.aka.scores.total > this.currentMatch.ao.scores.total){
          this.currentMatch.aka.scores.win = 1;
          this.currentMatch.ao.scores.win = 0;
        }else if(this.currentMatch.aka.scores.total < this.currentMatch.ao.scores.total){
          this.currentMatch.aka.scores.win = 0;
          this.currentMatch.ao.scores.win = 1;
        }else{
          if(this.currentMatch.aka.scores.senshu){
            this.currentMatch.aka.scores.win = 1;
            this.currentMatch.ao.scores.win = 0;
            
            this.currentMatch.aka.scores.total++;
          }else if(this.currentMatch.ao.scores.senshu){
            this.currentMatch.ao.scores.win = 1;
            this.currentMatch.aka.scores.win = 0;
            this.currentMatch.ao.scores.total++;
          }
        }
        this.localStorageService.set(Constants.monitor.ACTION, Constants.monitor.ACTION_PAUSE);
        this.localStorageService.set(Constants.monitor.COUNTDOWN, this.countdown);
        this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);
        console.log('finitoooooooooooooooooooooooo');
        this.pauseTimer();
      }
    },1000);

    
  }

  pauseTimer() {
    this.showStartButton = true;
    clearInterval(this.interval);
    this.localStorageService.set(Constants.monitor.ACTION, Constants.monitor.ACTION_PAUSE);
  }

  onCheckboxChange(){
    this.localStorageService.set(Constants.monitor.KUMITE, this.currentMatch);

    if(this.currentMatch.aka.scores.H){
      this.winMatch('AO');
    }
    if(this.currentMatch.ao.scores.H){
      this.winMatch('AKA');
    }
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
