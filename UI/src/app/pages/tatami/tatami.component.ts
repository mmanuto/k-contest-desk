import { Component, ViewEncapsulation, ViewChild, Pipe, PipeTransform, ElementRef  } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormControl, FormGroup, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { LocalStorageService } from 'angular-2-local-storage';
import { TatamiService } from './tatami.service';
import { categoryStatus, colorMapping, monitor, prova, categoryPhase, RoundMatch, RoundMatchTitles } from '../../constants';
import {interval,Subscription} from 'rxjs';
import {ModalDirective} from 'ngx-bootstrap/modal';
import * as Dataset from './datasets';
import { InMemoryDatabase } from './storage/memory';
import { BracketsManager } from 'brackets-manager';
import { environment } from '../../../environments/environment';
import { SelectDropDownModule } from 'ngx-select-dropdown';

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

interface Match {
  id: number;
  round: number;
  match_number: number;
  athlete_aka_inscription: any | null; // Oggetto atleta o null
  athlete_ao_inscription: any | null;
  score_aka: number;
  score_ao: number;
  winner_inscription_id: number | null;
  is_closed: boolean; // Flag per sapere se il match è finito
}

interface RoundGroup {
  roundId: number;
  name: string;
  matches: Match[];
}

const ROUND_NAMES = {
  1: 'Ottavi di Finale',
  2: 'Quarti di Finale',
  3: 'Semifinale',
  4: 'Finale',
  5: 'Ripescaggi'
};

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

  //CONSTANTS
  colorMapping = colorMapping;
  prova = prova;
  categoryStatus = categoryStatus;

  public searchText: string;
  categories: any[];
  athleteList: any[];
  tatamiStatus: any[];
  selectedCategory: any;
  labelCategory: string = '';
  alertsDismiss: any = [];
  
  currentUser;
  typeForm;
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
  
  // Round Kata a bandierine
  public rounds: RoundGroup[] = [];
  public isLoading = false;

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
    //TODO - Da rivedere
    this.getTatamiStatus();
    this.tatamiService.getKataList().subscribe((response: any) => {
      if(response.result.success){
        this.kataList = response.result.data;
      }
    });
    
    
  }


  //============================================================ SALVATAGGIO PUNTEGGIO SINGOLO ==================================================

  // Salvo punteggio KATA (adulti e bambini) - Percorso e palloncino
  checkSubmit(form: FormGroup, athleteId, type = null){

    //se ho già dei valori avviso che stiamo modificando un punteggio già salvato
    if(form.value.total || form.value.total_time){
      this.tempFormRequest = {
        form: form,
        athleteId: athleteId,
        type: type
      }
      this.dangerModal.show();
    }else{
      this.saveJudgeScore(form, athleteId, type);
    }
    
  }

  //funzione richiamata da dangerModal, modifica punteggio già salvato
  editScores(){
    this.saveJudgeScore(this.tempFormRequest.form, this.tempFormRequest.athleteId, this.tempFormRequest.type);
    this.dangerModal.hide();
  }

  /**
   * ================================
   * Calcola totali e salva punteggi
   * ================================
   * */
  saveJudgeScore(form: FormGroup, athleteId, type = null){

    //TODO - da rivedere logica per pulsanti predefiniti
    switch (type) {
      case 'kiken':
        if(this.typeForm == 'KIA'){
          this.patchKataForm(form, 0);
        }else if(this.typeForm == 'PER'){
          this.patchPercorsoForm(form);
        }else{
          this.patchBambiniForm(form, 0);
        }
        break;
      case 'min_score':
        if(this.typeForm == 'KIA'){
          this.patchKataForm(form, 5.0);
        }else if(this.typeForm == 'PER'){
          this.patchPercorsoForm(form);
        }else{
          this.patchBambiniForm(form, 5.0);
        }
        break;
    
      default:
        break;
    }
    if(form.invalid && type == null){
      console.log('INVALID');
    }else{
      form.value.category = this.typeForm;
      this.tatamiService.saveJudgeScore(form.value).subscribe((response:any) => {
        if(response.result.success){
          //this.getAtlheteList(form.value.athlete_inscription_id, athleteId);
          this.checkCategoryStatus(categoryStatus.DOING);
          this.getKataMatches(form.value.athlete_inscription_id, athleteId);
        }
      })

    }
  }
  
  /**
   * ===================================================================
   * Controlla lo satto della categoria e se necessario lo aggiorna - OK
   * ===================================================================
   * */
  checkCategoryStatus(status){

    if(this.selectedCategory.status == status)
    return;

    if(this.selectedCategory.status == categoryStatus.TODO && status == categoryStatus.OPEN ||
      this.selectedCategory.status == categoryStatus.OPEN && status == categoryStatus.DOING){
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

      this.selectedCategory.status = categoryStatus.CLOSED;
      this.athleteList = [];
      this.kumiteAthleteList = [];
      jQuery('#minimal').hide();
      jQuery('#girone-2').hide();
      jQuery('#girone-3').hide();
      let request = {
        categorycode_id: this.selectedCategory.categorycode_id,
        id: this.selectedCategory.id,
        status: categoryStatus.CLOSED,
        user_id: this.selectedCategory.user_id
      }
      this.tatamiService.updateCategoryStatus(request).subscribe((response: any) => {

      })
  }

   // --- Helper Functions con patchValue ---

  private patchBambiniForm(form: FormGroup, value: number){
    form.patchValue({
      kata_id: null,
      referee_1: value,
      referee_2: value,
      referee_3: value,
      penalty: 0 // Resetta penalità
    });
  }

  private patchKataForm(form: FormGroup, value: number) {
    form.patchValue({
      kata_id: null, // Reset del kata selezionato
      referee_1: value,
      referee_2: value,
      referee_3: value,
      referee_4: value,
      referee_5: value
    });
  }

  private patchPercorsoForm(form: FormGroup) {
    form.patchValue({
      minutes: 0,
      seconds: 0,
      milliseconds: 0,
      penalty: 0
    });
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

  //TODO - mettere una verifica da qualche parte. Questo pulsante deve essere cliccabile solo se tutti i punteggi sono valorizzati
  generateBrackets(){
    this.tatamiService.generateBrackets({categorycode_id: this.selectedCategory.categorycode.id}).subscribe((response: any) => {
        if(response.result.success){
          //this.getAtlheteList();
        }
      });
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

    // creo il form per il punteggio
    if(this.typeForm == prova.KATA_ADULTI){
      athlete.scoreForm = this.buildKataAdutiForm(athlete);
    }

    if(this.typeForm != 'PER'){
      let athleteToDisplay = {
        athlete: athlete.athlete_inscription.athlete,
        athlete_id: athlete.athlete_inscription.athlete_id,
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
      this.localStorageService.set(monitor.ATHLETE_1, null);
      this.localStorageService.set(monitor.KUMITE, null);
    }else if(type == 'AT'){
      this.localStorageService.set(monitor.ATHLETE_1, athlete);
      this.localStorageService.set('classifica', null);
      this.localStorageService.set(monitor.KUMITE, null);
    }else if(type == 'KUMITE'){
      this.localStorageService.set(monitor.ATHLETE_1, null);
      this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
      this.localStorageService.set('classifica', null);
    }else{
      this.localStorageService.set(monitor.ATHLETE_1, null);
      this.localStorageService.set(monitor.KUMITE, null);
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

    this.checkCategoryStatus(categoryStatus.OPEN);

    if(this.typeForm == prova.KATA_ADULTI){
      this.getKataMatches();
    }else{
      this.getAtlheteList();
    }
    
    
  }

  sortMarkets(array, sortArray){
    return [...array].sort(
      (a, b) => sortArray.indexOf(a.position) - sortArray.indexOf(b.position)
    )
  }

  getKataMatches(athleteId = null, athleteId_info = null){
    
    this.tatamiService.getKataMatches({categorycode_id: this.selectedCategory.categorycode_id}).subscribe((response: any) => {

      if(response.result.success){

        if(this.selectedCategory.current_phase == categoryPhase.BRACKETS){

          const flatMatches = response.result.data.athleteList[0].matches; // La tua lista piatta
          this.rounds = this.groupMatchesByRound(flatMatches);

          console.log(this.rounds);
        }else{
          this.athleteList = response.result.data.athleteList;

          this.athleteList.forEach(element => {

            element.show = false;

            if(athleteId_info && element.id == athleteId_info){

              element.scoreForm = this.buildKataAdutiForm(element);
              this.openInfo(element, 'AT');
              element.show = true;
            }

            if(element.total_score){
              // Creiamo un array temporaneo mappando ID arbitro e Valore
              // Questo ci permette di ordinare mantenendo il riferimento a "CHI" ha dato il voto
              const scores = [
                { id: 1, val: element.referee_1 },
                { id: 2, val: element.referee_2 },
                { id: 3, val: element.referee_3 },
                { id: 4, val: element.referee_4 },
                { id: 5, val: element.referee_5 }
              ];

              // Filtriamo eventuali valori null/undefined (se capita che un arbitro non ha votato)
              const validScores = scores.filter(s => s.val !== null && s.val !== undefined);

              // Se non abbiamo abbastanza voti, usciamo
              if (validScores.length > 0) {
                          
                // Ordiniamo l'array in base al valore (dal più piccolo al più grande)
                validScores.sort((a, b) => a.val - b.val);

                // Il primo elemento è SEMPRE il minimo (o uno dei minimi a pari merito)
                element.min_referee = validScores[0].id;

                // L'ultimo elemento è SEMPRE il massimo (o uno dei massimi a pari merito)
                element.max_referee = validScores[validScores.length - 1].id;
              }
            }         
      
            //element.scoreForm = this.buildKataAdutiForm(element);            
    
          });                               
        }
        
        

      }
    });
  }

  // Funzione per raggruppare i match per Round
  private groupMatchesByRound(matches: Match[]): RoundGroup[] {
    const groups: { [key: number]: RoundGroup } = {};

    matches.forEach(match => {
      if (!groups[match.round]) {
        groups[match.round] = {
          roundId: match.round,
          name: ROUND_NAMES[match.round] || `Turno ${match.round}`,
          matches: []
        };
      }
      groups[match.round].matches.push(match);
    });

    // Restituisce un array ordinato (es. Ottavi -> Quarti -> Semi -> Finale)
    return Object.values(groups).sort((a, b) => a.roundId - b.roundId);
  }

  // Azione per salvare il punteggio
  saveMatchKata(match: Match) {
    if (confirm(`Confermi il risultato: Rosso ${match.score_aka} - Blu ${match.score_ao}?`)) {
      //this.tatamiService.saveMatchResult(match).subscribe(() => {
        // Ricarica i dati per vedere l'avanzamento nel tabellone successivo
        //this.loadData(); 
      //});
    }
  }

  selectAthlete(athlete){
    console.log('entraaaaa');
  }

  getAtlheteList(athleteId = null, athleteId_info = null){

    this.tatamiService.getAthleteList({categorycode_id: this.selectedCategory.categorycode_id, readonly: false}).subscribe((response: any) => {

      //==================================================== KUMITE ==========================================================
  
      if(this.typeForm == prova.KUMITE || this.typeForm == prova.KUMITE_U12){

        this.kumiteMatches = []; //coppie primo giro di incontri
        this.kumiteAthleteList = []; //lista atleti del kumite

        //imposto iltempo in base a categoria
        //TODO: inserire la info a db

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
                club:this.kumiteAthleteList[index].athlete.club.club_name
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
  
            case prova.KATA_BAMBINI:
              element.scoreForm = this.buildKataBambiniForm(element);
              break;
            case prova.PERCORSO:
              element.scoreForm = this.buildPercorsoForm(element);
              break;
            case prova.PALLONCINO:
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
    this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(monitor.ACTION, 'MODIFY');
  }

  modifyTimer(operation){
    if(operation == 'PLUS'){
      this.countdown++;
    }else{
      this.countdown--;
    }
    this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(monitor.ACTION, 'MODIFY');
  }

  // Popolo form KATA ADULTI - OK
  buildKataAdutiForm(element){

    if (!element) return;
    const kataAdultiGroup = new FormGroup({
      id: new FormControl(element.id),
      name: new FormControl({value: element.athlete_inscription.athlete.cognome + ' ' + element.athlete_inscription.athlete.nome, disabled: true}, Validators.required),
      athlete_inscription_id: new FormControl(element.athlete_inscription.id),
      user_id: new FormControl(this.currentUser.id),
      kata_id: new FormControl(element.kata_id?? ''),
      referee_1: new FormControl(element.referee_1?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_2: new FormControl(element.referee_2?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_3: new FormControl(element.referee_3?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_4: new FormControl(element.referee_4?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      referee_5: new FormControl(element.referee_5?? '', Validators.compose(
        [Validators.max(10.0), Validators.min(5.0), Validators.required])),
      total: new FormControl(element.total_score?? '')
    });

    return kataAdultiGroup;

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


    this.localStorageService.set(monitor.KUMITE, this.currentMatch);
    this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(monitor.ACTION, '');
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
    this.localStorageService.set(monitor.KUMITE, this.currentMatch);

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
    this.localStorageService.set(monitor.KUMITE, this.currentMatch);

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
    this.localStorageService.set(monitor.ACTION, monitor.ACTION_PAUSE);
    this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
    this.localStorageService.set(monitor.KUMITE, this.currentMatch);
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
    this.checkCategoryStatus(categoryStatus.DOING);
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
    this.localStorageService.set(monitor.ACTION, monitor.ACTION_START);
    this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
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
        this.localStorageService.set(monitor.ACTION, monitor.ACTION_PAUSE);
        this.localStorageService.set(monitor.COUNTDOWN, this.countdown);
        this.localStorageService.set(monitor.KUMITE, this.currentMatch);
        console.log('finitoooooooooooooooooooooooo');
        this.pauseTimer();
      }
    },1000);

    
  }

  pauseTimer() {
    this.showStartButton = true;
    clearInterval(this.interval);
    this.localStorageService.set(monitor.ACTION, monitor.ACTION_PAUSE);
  }

  onCheckboxChange(){
    this.localStorageService.set(monitor.KUMITE, this.currentMatch);

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
