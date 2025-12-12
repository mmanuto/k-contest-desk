import { Component, ViewEncapsulation, ViewChild, Pipe, PipeTransform, ElementRef  } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormControl, FormGroup, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { LocalStorageService } from 'angular-2-local-storage';
import { TatamiService } from './tatami.service';
import { categoryStatus, colorMapping, monitor, prova, categoryPhase, RoundMatch, RoundMatchTitles, POINTS } from '../../constants';
import { interval,Subscription } from 'rxjs';
import { ModalDirective } from 'ngx-bootstrap/modal';
import { environment } from '../../../environments/environment';
import { DisplayInfoService } from '../displayinfo/displayinfo.service'; 
import { ToastrService } from 'ngx-toastr';
import { formatDate } from '@angular/common';

declare global {
  interface JQuery {
    (any): JQuery;
    bracket(options: any): JQuery;
  }
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
  categoryPhase = categoryPhase;

  public searchText: string;      //barra di ricerca
  //categories: any[];            //elenco categorie
  athleteList: any[];             //elenco atleti in categoria
  tatamiStatus: any[];            //categorie assegnate al tatami con relativo stato
  selectedCategory: any;          //Categoria selezionata
  labelCategory: string = '';     //Etichetta categoria
  alertsDismiss: any = [];
  
  currentUser;
  typeForm;
  kataList: any = [];
  mySubscription: Subscription;
  tempFormRequest: any = [];
  previousMatches = [];
  kumiteAthleteList: any[];
  kumiteMatches: any = [];
  currentMatch:any = [];          //Incontro corrente da mandare a video
  message = '';
  dataKata= [];
  showStartButton = true;
  allMatchesFlat = [];            //elenco match eliminazione diretta (KUMITE o KATA a bandierine)

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
  historyAka: any[] = [];
  historyAo: any[] = [];
  isRoundRobin: boolean;
  roundRobinMatches: any;
  roundRobinStandings: any[];

  constructor(private tatamiService: TatamiService, 
    public fb: FormBuilder,
    private displayService: DisplayInfoService,
    public toastrService: ToastrService,
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
        console.log(this.kataList);
      }
    });
    
    
  }

    // ELENCO CATEGORIE ASSEGNATE AL TATAMI
  getTatamiStatus(){
    let request = {
      id: this.currentUser.id
    };
    this.tatamiService.getTatamiStatus(request).subscribe((response: any) => {
      this.tatamiStatus = response.result.data;
    });
  }

  // ------------------------------------------------- SELEZIONE CATEGORIA ----------------------------------------
  
  // ELENCO ATLETI NELLA CATEGORIA SELEZIONATA
  onSelect(category){
    
    this.selectedCategory = category;

    this.typeForm = category.categorycode.id.substring(0, 3);
    this.labelCategory = `${category.categorycode.specialita} - ${category.categorycode.categoria} ${category.categorycode.grado} - ${category.categorycode.sesso} ${category.categorycode.cat_peso}`
    this.athleteList = [];
    this.kumiteAthleteList = [];
    this.kumiteMatches = [];

    this.checkCategoryStatus(categoryStatus.OPEN);

    this.getCategoryState();
    
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
      if(this.typeForm == prova.PERCORSO){
        
        this.saveTimedScore(form, athleteId);        

      }else{
        this.saveJudgeScore(form, athleteId, type);
      }
      
    }
    
  }

  deleteAthlete(athlete){

    let request = {
      athlete_inscription_id: athlete.athlete_inscription_id,
      categorycode_id: athlete.categorycode_id,
      id: athlete.id
    }

    this.tatamiService.deleteInscription(request).subscribe((response:any) => {
        if(response.result.success){
          this.getCategoryState();
        }
      })
  }

  //funzione richiamata da dangerModal, modifica punteggio già salvato
  editScores(){
    if(this.typeForm == prova.PERCORSO){
      this.saveTimedScore(this.tempFormRequest.form, this.tempFormRequest.athleteId);
    }else{
      this.saveJudgeScore(this.tempFormRequest.form, this.tempFormRequest.athleteId, this.tempFormRequest.type);
    }    
    this.dangerModal.hide();
  }

  /**
   * ================================
   * Calcola totali e salva punteggi
   * ================================
   * */
  saveJudgeScore(form: FormGroup, athleteId, type = null){

    if(type == 'min_score'){
      if(this.typeForm == 'KIA'){
        this.patchKataForm(form, 5.0);
      }else if(this.typeForm == 'PER'){
        this.patchPercorsoForm(form);
      }else{
        this.patchBambiniForm(form, 5.0);
      }
    }

    if(form.invalid && type == null){

    }else{
      form.value.category = this.typeForm;
      this.tatamiService.saveJudgeScore(form.value).subscribe((response:any) => {
        if(response.result.success){
          //this.getAtlheteList(form.value.athlete_inscription_id, athleteId);
          this.checkCategoryStatus(categoryStatus.DOING);
          this.getCategoryState(athleteId);
        }
      })

    }
  }

  saveTimedScore(form: FormGroup, athleteId){

    console.log(form.value);
        
        // Prepara il payload
        const payload = {
          id: form.value.id, // L'ID della riga results_timed
          categorycode_id: this.selectedCategory.categorycode_id,
          minutes: form.value.minutes,
          seconds: form.value.seconds,
          milliseconds: form.value.milliseconds,
          penalties: form.value.penalties
        };

        this.tatamiService.saveTimedScore(payload).subscribe((response:any) => {
        if(response.result.success){
          this.getCategoryState(athleteId);
        }
      })
  }

  getCategoryState(athleteId_info = null){
  
  // 1. Chiama il backend per avere lo stato del tabellone
   
    this.tatamiService.getCategoryState({categorycode_id: this.selectedCategory.categorycode_id}).subscribe((response: any) => {

      if(response.result.success){

        this.selectedCategory.current_phase = response.result.data.phase;
        this.selectedCategory.status = response.result.data.status;

        switch (this.selectedCategory.current_phase) {
          case categoryPhase.BRACKETS:
          case categoryPhase.BRACKETS_FINALIZING:
            
            // 2. I dati arrivano già pronti dal backend (lista di match)
            const flatMatches = response.result.data.athleteList;

            const isRoundRobin = flatMatches[0].matches.length === 3 && flatMatches[0].round === 10;
            
            console.log(flatMatches);
            console.log(isRoundRobin);

            if (isRoundRobin) {
              this.isRoundRobin = true;
              this.roundRobinMatches = flatMatches[0].matches;
              this.calculateRoundRobinStandings(); // Calcola classifica live
              console.log(this.roundRobinStandings);
            }else{
              //popolo jquerybrackets
              const bracketData = this.transformForJQueryBracket(flatMatches);

              // FIX: Avvolgi l'inizializzazione in setTimeout
              setTimeout(() => {
          
                jQuery('#minimal').empty(); 

                // 4. Inizializza la libreria (senza logica if/else infinita!)
                jQuery('#minimal').bracket({
                    teamWidth: 250,
                    scoreWidth: 45,
                    matchMargin: 70,
                    roundMargin: 70,
                    init: bracketData,
                    save: function(){},
                    decorator:{
                      edit:(container, data, doneCb) => this.edit_fn(container, data, doneCb), 
                      render: (container, data, score, state) => this.render_fn(container, data, score, state)
                    }
                });

              }, 0); // Basta 0ms per "saltare" un ciclo di rendering

              } 
              
              if(this.selectedCategory.categorycode.categoria == 'UNDER 12'){
                  this.countdown = 60;
                }else if(this.selectedCategory.categorycode.categoria == 'ESORDIENTI'){
                  this.countdown = 90;
                }else{
                  this.countdown = 120;
                } 

            break;
          case categoryPhase.FINALIZED:
            
            this.athleteList = [];
            response.result.data.athleteList.forEach(element => {
              if(element.final_ranking <= 4){
                this.athleteList.push(element);
              }
              
            });

            if(this.typeForm != prova.PERCORSO){
              this.syncDataToDisplay('FINALIZED');
            } 

          case categoryPhase.AWAITING_BRACKETS:

            this.athleteList = response.result.data.athleteList;
            this.syncDataToDisplay('FINALIZED');

          default:

            this.athleteList = response.result.data.athleteList;

            this.athleteList.forEach(element => {

              element.show = false;
              
              console.log(athleteId_info);
              if(athleteId_info && element.id == athleteId_info){
                this.displayAthlete(element);
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
      
            });

            break;
        }    

      }
    });
  }
  
  // Funzione per calcolare la classifica lato frontend (solo visualizzazione)
calculateRoundRobinStandings() {
    let stats = {};

    // Inizializza
    this.roundRobinMatches.forEach(m => {
        [m.athlete_aka_inscription, m.athlete_ao_inscription].forEach(ath => {
            if (ath && !stats[ath.id]) {
                stats[ath.id] = {
                    name: `${ath.athlete.cognome} ${ath.athlete.nome}`,
                    club: ath.athlete.club.club_name,
                    wins: 0,
                    score: 0,
                    conceded: 0,
                    diff: 0
                };
            }
        });
    });

    // Calcola
    this.roundRobinMatches.forEach(m => {
        if (m.winner_inscription_id) {
            // Punti
            const akaId = m.athlete_aka_inscription.id;
            const aoId = m.athlete_ao_inscription.id;
            
            stats[akaId].score += Number(m.score_aka);
            stats[akaId].conceded += Number(m.score_ao);
            stats[aoId].score += Number(m.score_ao);
            stats[aoId].conceded += Number(m.score_aka);

            // Vittorie
            stats[m.winner_inscription_id].wins++;
        }
    });

    // Calcola diff e trasforma in array
    this.roundRobinStandings = Object.values(stats).map((s: any) => {
        s.diff = s.score - s.conceded;
        return s;
    });

    // Ordina (Vittorie > Punti Fatti > Differenza)
    this.roundRobinStandings.sort((a, b) => {
        if (b.wins !== a.wins) return b.wins - a.wins;
        if (b.score !== a.score) return b.score - a.score;
        return b.diff - a.diff;
    });
}

    /**
   * ==============================
   * HELPER BRACKET MANAGER - OK
   * ==============================
   * */
  render_fn(container, data, score, state) {
              switch(state) {
                case "empty-bye":
                  container.append("--")
                  return;
                case "empty-tbd":
                  container.append("In attesa")
                  return;
            
                case "entry-no-score":
                case "entry-default-win":
                case "entry-complete":
                  container.append(data.name)
                  return;
              }
            }
            
  
            /* Edit function is called when team label is clicked */
            edit_fn(container, data, doneCb) {
              if (!data || !data.id) return;

              // 1. Capiamo in che ROUND abbiamo cliccato
              // jQuery Bracket usa classi .round per le colonne. L'indice 0 è il primo turno visualizzato.
              // Nota: container è un oggetto jQuery.
              const roundIndex = container.closest('.round').index(); 
              
              // 2. Calcoliamo il 'round' reale del backend
              // Se minRound era 2 (Semifinali), l'index 0 corrisponde al round 2.
              const minRound = Math.min(...this.allMatchesFlat.map(m => m.round));
              const currentRealRound = minRound + roundIndex;

              // 3. CERCHIAMO IL MATCH CORRETTO
              // Cerchiamo nell'array di tutti i match quello che:
              // - Appartiene a questo Round
              // - Contiene questo Atleta (o come AKA o come AO)
              const targetMatch = this.allMatchesFlat.find(m => 
                  m.round === currentRealRound && 
                  (m.athlete_aka_inscription_id === data.id || m.athlete_ao_inscription_id === data.id)
              );

              if (targetMatch) {
                  console.log("Match Trovato:", targetMatch);
                  // ORA HAI L'OGGETTO MATCH COMPLETO con il suo ID univoco!
                  this.openMatch(targetMatch); 
              } else {
                  console.error("Impossibile trovare il match per questo atleta in questo round.");
              }

              //doneCb(data);
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


  /**
   * ===================================================================
   * Genera classifche per percorso palloncino e kata bambini - OK
   * ===================================================================
   * */
  finalizeScoreAndTimedCategory(categorycode_id){

    if(this.typeForm == prova.PERCORSO){

      this.tatamiService.finalizeTimedCategory({categorycode_id: categorycode_id}).subscribe((response:any) =>{
        if(response.result.success){
          this.getCategoryState();
        }
      });

    }else{
      this.tatamiService.finalizeScoreOnlyCategory({categorycode_id: categorycode_id}).subscribe((response:any) =>{
        if(response.result.success){
          this.getCategoryState();
        }
      });
    }
  }
  

  closeCategory(){

      this.selectedCategory.status = categoryStatus.CLOSED;
      
      let request = {
        categorycode_id: this.selectedCategory.categorycode_id,
        id: this.selectedCategory.id,
        status: categoryStatus.CLOSED,
        user_id: this.selectedCategory.user_id
      }
      this.tatamiService.updateCategoryStatus(request).subscribe((response: any) => {

        if(response.result.success){
          
          this.selectedCategory = [];
          this.athleteList = [];
          this.kumiteAthleteList = [];
          this.roundRobinStandings = [];
          jQuery('#minimal').hide();
        }

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

  openDetailModal(athlete: any) {
  //TODO Se usi Bootstrap Modal
  //this.selectedAthleteForDetail = athlete;
  //$('#detailModal').modal('show');
}
  // ================================================================ ELABORAZIONE PUNTEGGI CATEGORIA ==========================================

  generateBrackets(){
    this.tatamiService.generateBrackets({categorycode_id: this.selectedCategory.categorycode.id}).subscribe((response: any) => {
        if(response.result.success){
          this.getCategoryState();
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



    switch (this.selectedCategory.current_phase) {
      case categoryPhase.JUDGING_PANEL:
      case categoryPhase.JUDGING_FINALIZING:
        athlete.scoreForm = this.buildJudgeForm(athlete);
        
        let dataDisplay = {
          type: 'ATHLETE_SCORE',
          athlete_inscription: athlete.athlete_inscription,
          category: this.selectedCategory,
          score: athlete.total_score
        }

        console.log('Sending to display:', dataDisplay);
        this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);
        break;
      case categoryPhase.TIME_PANEL:
      case categoryPhase.TIMED_FINALIZING:
        athlete.scoreForm = this.buildTimerForm(athlete);
        break;
    
      default:
        break;
    } 
  }

  // Azione per salvare il punteggio
  saveMatch() {

    let formOk = false;
    let dataDisplay = null;

    console.log(this.currentMatch);

    if(this.typeForm == prova.KATA_ADULTI){

      if(this.currentMatch.kata_id_aka && this.currentMatch.kata_id_ao && this.currentMatch.score_aka + this.currentMatch.score_ao > 0) {
        formOk = true;
        dataDisplay = {
          type: 'KATA_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory
        } 

        console.log('Sending to display:', dataDisplay);
        this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);
      }      

    }else {

      if(this.currentMatch.winner_inscription_id == this.currentMatch.athlete_aka_inscription_id && this.currentMatch.score_aka <= this.currentMatch.score_ao){
        this.currentMatch.score_aka = this.currentMatch.score_ao +1;
        }
        if(this.currentMatch.winner_inscription_id == this.currentMatch.athlete_ao_inscription_id && this.currentMatch.score_aka >= this.currentMatch.score_ao){
          this.currentMatch.score_ao = this.currentMatch.score_aka +1;
        }
        formOk = true;
  
        this.checkCategoryStatus(categoryStatus.DOING);

        dataDisplay = {
          type: 'KUMITE_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory,
          countdown : this.countdown
        }

    }

    
    if(formOk && dataDisplay){      

      this.tatamiService.saveMatchResult(this.currentMatch).subscribe(() => {
        // Ricarica i dati per vedere l'avanzamento nel tabellone successivo

        this.kumiteModal.hide();
        this.getCategoryState(); 
        this.closeMatch();
      });
    }     
    
  }

  getKumiteFinalRanking(category_id){

    this.tatamiService.getKumiteFinalRanking({categorycode_id: category_id}).subscribe(() => {
        // Ricarica i dati per vedere l'avanzamento nel tabellone successivo
        this.getCategoryState(); 

      });
  }

  selectAthlete(athlete){
    console.log('entraaaaa');
  }


  resetTimer(){
    if(this.selectedCategory.categorycode.categoria == 'UNDER 12'){
      this.countdown = 60;
    }else if(this.selectedCategory.categorycode.categoria == 'ESORDIENTI'){
      this.countdown = 90;
    }else{
      this.countdown =120;
    }
    
    this.syncDataToDisplay('KUMITE_BRACKETS');
  }

  modifyTimer(operation){
    if(operation == 'PLUS'){
      this.countdown++;
    }else{
      this.countdown--;
    }

    this.syncDataToDisplay('KUMITE_BRACKETS');
  }

  // Popolo form KATA ADULTI - OK
  buildJudgeForm(element){

    if (!element) return;

    //console.log(element);

    // 1. Definisci i controlli base in un oggetto
    const controlsConfig: any = {
      id: new FormControl(element.id),
      name: new FormControl({value: element.athlete_inscription.athlete.cognome + ' ' + element.athlete_inscription.athlete.nome, disabled: true}, Validators.required),
      athlete_inscription_id: new FormControl(element.athlete_inscription.id),
      user_id: new FormControl(this.currentUser.id),
      referee_1: new FormControl(element.referee_1 ?? '', [Validators.max(10.0), Validators.min(5.0), Validators.required]),
      referee_2: new FormControl(element.referee_2 ?? '', [Validators.max(10.0), Validators.min(5.0), Validators.required]),
      referee_3: new FormControl(element.referee_3 ?? '', [Validators.max(10.0), Validators.min(5.0), Validators.required]),
      total: new FormControl(element.total_score?? '')
    };

    // 2. Aggiungi le chiavi all'oggetto se la condizione è vera
    if (this.typeForm == prova.KATA_ADULTI || this.typeForm == prova.KATA_SQUADRE) {
      controlsConfig['referee_4'] = new FormControl(element.referee_4 ?? '', [Validators.max(10.0), Validators.min(5.0), Validators.required]);
      controlsConfig['referee_5'] = new FormControl(element.referee_5 ?? '', [Validators.max(10.0), Validators.min(5.0), Validators.required]);
      controlsConfig['kata_id'] = new FormControl(element.kata_id ?? '');
    }

    if (this.typeForm == prova.KATA_BAMBINI) {
      controlsConfig['kata_id'] = new FormControl(element.kata_id ?? '');
    }

    if (this.typeForm == prova.PALLONCINO) {
      controlsConfig['partial_score'] = new FormControl(element.partial_score ?? '');
      controlsConfig['penalties_points'] = new FormControl(element.penalties_points ?? '');
    }

    // 3. Crea il FormGroup finale
    const formControl = new FormGroup(controlsConfig);

    return formControl;

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


      // Popolo form PERCORSO
  buildTimerForm(element){

    const percorsoGroup = new FormGroup({
      id: new FormControl(element.id),
      name: new FormControl({value: element.athlete_inscription.athlete.cognome + ' ' + element.athlete_inscription.athlete.nome, disabled: true}, Validators.required),
      athlete_inscription_id: new FormControl(element.athlete_inscription.id),
      user_id: new FormControl(this.currentUser.id),
      minutes: new FormControl(element.minutes?? ''),
      seconds: new FormControl(element.seconds?? ''),
      milliseconds: new FormControl(element.milliseconds?? ''),
      penalties: new FormControl(element.penalties?? ''),
      total_time: new FormControl(element.total_time ? formatDate(element.total_time, 'mm:ss:SSS', 'en-US', 'UTC') : '')
    });

    return percorsoGroup;

  }

// ========================================================  KUMITE =======================================================================

  openMatch(data){

    console.log(data);
    console.log(this.selectedCategory);

    let dataDisplay = null;

    this.currentMatch = data;

    if(this.typeForm == prova.KATA_ADULTI){
      if (this.currentMatch) {
        this.loadHistory(this.currentMatch.athlete_aka_inscription_id, 'AKA');
        this.loadHistory(this.currentMatch.athlete_ao_inscription_id, 'AO');
      }

      dataDisplay = {
          type: 'KATA_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory
        }

    }else{
      dataDisplay = {
          type: 'KUMITE_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory,
          countdown: this.countdown
        }
    }
    
    
    this.kumiteModal.show();
    
    console.log('Sending to display:', dataDisplay);
    this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);

  }

  loadHistory(athleteId: number, side: 'AKA' | 'AO') {
  if (!athleteId) return;

  const payload = {
    athlete_inscription_id: athleteId,
    categorycode_id: this.currentMatch.categorycode_id
  };

  this.tatamiService.getAthleteHistory(payload).subscribe((res: any) => {
    if (res.result.success) {
      if (side === 'AKA') this.historyAka = res.result.data;
      else this.historyAo = res.result.data;
    }
  });
}


  closeMatch(){
    this.kumiteModal.hide();
  }

/**
 * Gestisce l'aggiunta/rimozione punti per entrambi gli atleti.
 * @param side 'aka' oppure 'ao'
 * @param scoreType 1 (Yuko), 2 (Waza-ari), 3 (Ippon)
 * @param action 'ADD' oppure 'REMOVE'
 */
editScore(side: 'aka' | 'ao', scoreType: number, action: 'ADD' | 'REMOVE') {
  
  // 1. Determina il nome della proprietà dinamica (es. 'yuko_ao', 'ippon_aka')
  let techniquePrefix = '';
  switch(scoreType) {
      case 1: techniquePrefix = 'yuko'; break;
      case 2: techniquePrefix = 'wazaari'; break;
      case 3: techniquePrefix = 'ippon'; break;
  }
  
  const fieldName = `${techniquePrefix}_${side}`; // es. 'wazaari_ao'

  console.log(fieldName);

  // 2. Esegui l'azione
  if (action === 'ADD') {
    // Incrementa (gestisce anche il caso in cui sia undefined o null iniziando da 0)
    this.currentMatch[fieldName] = (this.currentMatch[fieldName] || 0) + 1;
  } 
  else if (action === 'REMOVE') {
    // FIX CRITICO: Controlla se QUESTA tecnica è > 0, non il totale!
    if (this.currentMatch[fieldName] > 0) {
      this.currentMatch[fieldName]--;
    } else {
      return; // Non fare nulla se siamo a 0, evita numeri negativi
    }
  }

  // 3. Ricalcola il Totale per quel lato
  // Recuperiamo i valori aggiornati (sicuri che siano numeri)
  const yuko = this.currentMatch[`yuko_${side}`] || 0;
  const wazaari = this.currentMatch[`wazaari_${side}`] || 0;
  const ippon = this.currentMatch[`ippon_${side}`] || 0;

  // Aggiorna il campo totale (es. score_ao)
  this.currentMatch[`score_${side}`] = (yuko * POINTS[1]) + (wazaari * POINTS[2]) + (ippon * POINTS[3]);

  // 4. Aggiorna Monitor Pubblico e Storage
  let dataDisplay = {
          type: 'KUMITE_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory,
          countdown: this.countdown
        }
    
    console.log('Sending to display:', dataDisplay);
    this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);

  // 5. Controllo Vittoria (Gap 8 punti)
  this.checkVictoryByGap();
}

/**
 * Controlla se c'è una vittoria per scarto (8 punti)
 */
checkVictoryByGap() {
  const akaTot = this.currentMatch.score_aka || 0;
  const aoTot = this.currentMatch.score_ao || 0;
  const GAP_LIMIT = 8;

  if (akaTot - aoTot >= GAP_LIMIT) {
    this.winMatch('AKA');
  } else if (aoTot - akaTot >= GAP_LIMIT) {
    this.winMatch('AO');
  }
}

  winMatch(color){
    if(color == 'AKA'){
      this.currentMatch.winner_inscription_id = this.currentMatch.athlete_aka_inscription_id;
      this.currentMatch.loser_inscription_id = this.currentMatch.athlete_ao_inscription_id;
      
    }else{
      this.currentMatch.winner_inscription_id = this.currentMatch.athlete_ao_inscription_id;
      this.currentMatch.loser_inscription_id = this.currentMatch.athlete_aka_inscription_id;
    }

    this.countdown = 0;
    let dataDisplay = {
          type: 'KUMITE_BRACKETS',
          currentMatch: this.currentMatch,
          category: this.selectedCategory,
          countdown: this.countdown
        }
    
    console.log('Sending to display:', dataDisplay);
    this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);
    this.pauseTimer();
  }  

  startTimer() {
    this.showStartButton = false;
  
  
    this.syncDataToDisplay('KUMITE_BRACKETS');
    
    if(this.countdown > 0) {
      this.countdown--;
      this.syncDataToDisplay('KUMITE_BRACKETS');
    }
    this.interval = setInterval(() => {
      if(this.countdown > 0) {
        this.countdown--;
        this.syncDataToDisplay('KUMITE_BRACKETS');
      } else {
        this.pauseTimer();
        this.handleMatchEnd();            
      }
    },1000);
    
  }

  handleMatchEnd() {

  // 1. CONTROLLO PUNTEGGIO TOTALE
  if(this.currentMatch.score_aka > this.currentMatch.score_ao){
    this.winMatch('AKA');
    return;
  }
  
  if (this.currentMatch.score_aka < this.currentMatch.score_ao) {
    this.winMatch('AO');
    return;
  }

  // 2. CONTROLLO SENSHU (Vantaggio)
  if (this.currentMatch.senshu_aka) {
    console.log("Vittoria AKA per Senshu");
    this.winMatch('AKA');
    return;
  } 
  
  if (this.currentMatch.senshu_ao) {
    console.log("Vittoria AO per Senshu");
    this.winMatch('AO');
    return;
  }

  // LA PARITA CONTINUA

  // 3. CONTROLLO IPPON (Chi ne ha di più vince)
  if (this.currentMatch.ippon_aka > this.currentMatch.ippon_ao) {
    console.log("Vittoria AKA per maggior numero di Ippon");
    this.winMatch('AKA');
    return;
  }
  
  if (this.currentMatch.ippon_ao > this.currentMatch.ippon_aka) {
    console.log("Vittoria AO per maggior numero di Ippon");
    this.winMatch('AO');
    return;
  }

  // 3. CONTROLLO WAZA-ARI (Chi ne ha di più vince)
  // (Nota: se Ippon sono pari, guardiamo i Waza-ari)
  if (this.currentMatch.wazaari_aka > this.currentMatch.wazaari_ao) {
    console.log("Vittoria AKA per maggior numero di Waza-ari");
    this.winMatch('AKA');
    return;
  }

  if (this.currentMatch.wazaari_ao > this.currentMatch.wazaari_aka) {
    console.log("Vittoria AO per maggior numero di Waza-ari");
    this.winMatch('AO');
    return;
  } 

  // 5. HANTEI (Decisione Arbitrale)
  alert("PARITÀ ASSOLUTA (Hantei): Seleziona manualmente il vincitore.");
}

syncDataToDisplay(type: 'KUMITE_BRACKETS' | 'KATA_BRACKETS' | 'FINALIZED') {

  let dataDisplay = null;

  if (type == 'FINALIZED'){

    dataDisplay = {
      type: type,
      athleteList: this.athleteList, 
      category: this.selectedCategory
    };

  }else {

    dataDisplay = {
      type: type,
      countdown: this.countdown, // Il tempo attuale
      currentMatch: this.currentMatch, 
      category: this.selectedCategory
    };
  }
  
    console.log('Sending to display:', dataDisplay);
  
  // Invia tramite BroadcastChannel (molto più veloce del LocalStorage)
  this.displayService.updateDisplay(dataDisplay, environment.DISPLYINFO_URL);
}

  pauseTimer() {
    this.showStartButton = true;
    clearInterval(this.interval);
    this.syncDataToDisplay('KUMITE_BRACKETS');
  }

  onCheckboxChange(){
    
    this.syncDataToDisplay('KUMITE_BRACKETS');

    if(this.currentMatch.hans_aka){
      this.winMatch('AO');
    }
    if(this.currentMatch.hans_ao){
      this.winMatch('AKA');
    }
  }

  // bracket-adapter.service.ts

public transformForJQueryBracket(matches: any[]): any {

  if (!matches || matches.length === 0) {
    return { teams: [], results: [] };
  }
  // 1. APPIATTIMENTO (Flattening)
    // Se i dati arrivano raggruppati per round (come da screenshot), dobbiamo estrarli.
    this.allMatchesFlat = [];

    // Controlliamo se inputData ha la struttura raggruppata
    if (matches.length > 0 && matches[0].matches) {
        // Estrai l'array 'matches' da ogni round e uniscili
        matches.forEach(roundGroup => {
            if (roundGroup.matches) {
                this.allMatchesFlat = this.allMatchesFlat.concat(roundGroup.matches);
            }
        });
    } else {
        // Altrimenti assumiamo sia già piatto
        this.allMatchesFlat = matches;
    }

    if (this.allMatchesFlat.length === 0) {
        return { teams: [], results: [] };
    }

    // 2. Ora lavoriamo su 'allMatches' che è l'array piatto che ci aspettavamo
    const rounds = this.allMatchesFlat.map(m => m.round);
    const minRound = Math.min(...rounds);
    
    const teams = [];
    const results = [];

    // 2. Popola 'teams' usando SOLO i match del round più basso (il punto di partenza)
    // Ordiniamo per match_number per garantire l'ordine corretto nel tabellone (1vs8, 2vs7...)
    const startingMatches = this.allMatchesFlat
        .filter(m => m.round === minRound)
        .sort((a, b) => a.match_number - b.match_number);
    
    startingMatches.forEach(match => {
        
        const akaObj = match.athlete_aka_inscription ? { 
            name: match.athlete_aka_inscription.athlete.cognome + ' ' + match.athlete_aka_inscription.athlete.nome + ' ' + match.athlete_aka_inscription.athlete.club.club_name, 
            id: match.athlete_aka_inscription.id,
            matchId: match.id // ID del primo incontro
        } : null; // Usa null se BYE

        const aoObj = match.athlete_ao_inscription ? { 
            name: match.athlete_ao_inscription.athlete.cognome + ' ' + match.athlete_ao_inscription.athlete.nome + ' ' + match.athlete_ao_inscription.athlete.club.club_name, 
            id: match.athlete_ao_inscription.id,
            matchId: match.id
        } : null;

        teams.push([akaObj, aoObj]);
    });

    // 3. Popola 'results' per TUTTI i round
    // jQuery Bracket si aspetta un array di array di array
    // [ [ [1,0], [2,1] ], [ [3,2] ], ... ]
    
    // Raggruppa match per round
    const matchesByRound = this.groupBy(this.allMatchesFlat, 'round'); 

    // Importante: Dobbiamo iterare dai round più bassi a quelli più alti
    const sortedRoundKeys = Object.keys(matchesByRound).map(Number).sort((a, b) => a - b);

    sortedRoundKeys.forEach(roundKey => {
        const roundMatches = matchesByRound[roundKey];
        
        if (roundMatches) {
          // Ordina anche i match dentro il round! Altrimenti i punteggi si disallineano
          roundMatches.sort((a, b) => a.match_number - b.match_number);

          const roundResults = [];

          roundMatches.forEach(match => {
              // Logica sicura per i punteggi:
              // Se winner_id è null, significa che il match non è finito -> [null, null]
              // Altrimenti usa i punteggi (anche se sono 0)
              if (match.winner_inscription_id !== null && match.score_aka !== undefined) {
                  // Converti in numeri per sicurezza
                  roundResults.push([ Number(match.score_aka), Number(match.score_ao) ]);
              } else {
                  roundResults.push([null, null]);
              }
          });

          results.push(roundResults);
        }
    });

    console.log('JQuery Bracket Data:', { teams, results }); // Debug utile

    return {
        teams: teams,
        results: results
    };


}

// Helper
private groupBy(xs, key) {
  return xs.reduce(function(rv, x) {
    (rv[x[key]] = rv[x[key]] || []).push(x);
    return rv;
  }, {});
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
