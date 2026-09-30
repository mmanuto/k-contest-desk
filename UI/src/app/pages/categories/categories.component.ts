import { Component, OnInit, ViewChild, ViewEncapsulation} from '@angular/core';
import { UntypedFormBuilder, FormGroup} from '@angular/forms';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { ToastrService } from 'ngx-toastr';
import { CategoriesService } from './categories.service';
import { MenuService } from '../../theme/components/menu/menu.service';
import { TranslateService } from '@ngx-translate/core';
import { ModalDirective } from 'ngx-bootstrap/modal';
import {interval,Subscription} from 'rxjs';
import {categoryStatus} from '../../constants';

import * as Dataset from '../tatami/datasets';
import { InMemoryDatabase } from '../tatami/storage/memory';
import { BracketsManager } from 'brackets-manager';
 
declare global {
  interface JQuery {
    (arg0: any): JQuery;
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
  selector: 'app-membership',
  templateUrl: './categories.component.html',
  styleUrls: ['./categories.component.scss'],
  encapsulation: ViewEncapsulation.None,
  providers: [ CategoriesService, MenuService ]
})
export class CategoriesComponent implements OnInit {

  @ViewChild('primaryModal') public primaryModal: ModalDirective | undefined;
  @ViewChild('listModal') public listModal: ModalDirective | undefined;
  @ViewChild('rankingModal') public rankingModal: ModalDirective | undefined;
  @ViewChild('modalClassifica') public modalClassifica: ModalDirective | undefined;

  public searchText: string = '';
  public p:any;
  public type:string = 'list';
  

  categories: any[] = [];
  athleteList: any[] = [];
  tatamiList: any[] = [];
  tatamiStatus: any[] = [];
  public athletestNumber = null;
  public totaleProve = null
  selectedCategory: any = null;
  mySubscription: Subscription;
  categoryStatus = categoryStatus;
  alertsDismiss: any = [];
  message: string = '';
  public kumiteMatches:any[] = [];
  public minimalData = {};
  public secondGirone = {};
  public thirdGirone = {};
  public dettaglio = false;

  selectedCategoryForRanking: any = null;
  rankingData: any[] = [];

  rankingList: any[] = []; // Qui salviamo i dati

  public tatamiForm: FormGroup;
  
  constructor(public fb:UntypedFormBuilder, 
              public toastrService: ToastrService,
              public translateService: TranslateService,
              public categoriesService:CategoriesService,
              public menuService:MenuService, 
              public modalService: NgbModal) {

                this.mySubscription= interval(50000).subscribe((x =>{
                  this.getCategories();
              })); 

    this.tatamiForm = this.fb.group({
      'user_id': [''],
      'categorycode_id': ['']
    });
  }

  ngOnInit() {

    this.categoriesService.getTotaleIscrizioni().subscribe((response: any) => {
      this.athletestNumber = response.result.data;
    });

    this.categoriesService.getTotaleProve().subscribe((response: any) => {
      this.totaleProve = response.result.data;
    });

    this.getCategories();

    this.categoriesService.getTatami().subscribe((response: any) => {
      this.tatamiList = response.result.data;
    });

    this.getTatamiStatus();

    //this.categoriesService.getCompetitions().subscribe((response: any) => {
      //console.log(response);
    //});

  }

  getCategories(){
    this.categoriesService.getCategories().subscribe((response: any) => {
      this.categories = response.result.data;
      console.log(this.categories);
    });
  }

  public toggle(type: string){
    this.type = type;
  }

  getTatamiStatus(){
    this.categoriesService.getTatamistatus().subscribe((response: any) => {
      this.tatamiStatus = response.result.data;
    });
  }

  showTatamiStatus(){
    this.getTatamiStatus();
  }

  //====================================================================================================
  // Dividi categoria
  //====================================================================================================

  splitCategory(category: { specialita: any; categoria: any; grado: any; sesso: any; }){
    console.log(category);
    if (window.confirm(`Proseguendo con l'operazione, la categoria ${category.specialita} - ${category.categoria} - ${category.grado} ${category.sesso} verrà divisa in Maschile e Femminile. Continuare?`)){
      this.categoriesService.splitCategory(category).subscribe((response: any) => {

      });
      
    }
  }

  //====================================================================================================
  // Assegna a tatami
  //====================================================================================================
  openTatamiModal(category_id: any){
    this.message = '';
    this.getTatamiStatus();

    this.tatamiForm = this.fb.group({
      'user_id': [''],
      'categorycode_id': [category_id]
    });

    this.tatamiStatus.forEach(tatami => {
      tatami.userCategories.forEach((category: { id: any; status: string; }) => {
          if(category.id == this.tatamiForm.value.categorycode_id){
            this.message = 'La categoria selezionata è già assegnata al ' + tatami.name;
            if(category.status == categoryStatus.DOING){
              this.message += ' ed è già in corso! Proseguendo la categoria verrà spostata sul tatami selezionato.';
            }else{
              this.message += '. Proseguendo la categoria verrà spostata sul tatami selezionato.';
            }
          }
      });
    });

    if(this.primaryModal){
      this.primaryModal.show();
    }
    
  }

  assignTatami(){
    let request = {
      user_id: this.tatamiForm.value.user_id,
      categorycode_id: this.tatamiForm.value.categorycode_id,
      status: categoryStatus.TODO
    }

    this.categoriesService.saveTatamiAssegnee(request).subscribe((response: any) => {

      if(response.result.success){

     /*   let requestWeb = {
          tatami: this.tatamiForm.value.user_id,
          competition_id: 'SCCSC-2026',
          categorycode_id: this.tatamiForm.value.categorycode_id,
          status: categoryStatus.TODO
        }

        this.categoriesService.updateTatamiWeb(requestWeb).subscribe((response: any) => {

        });*/

        this.alertsDismiss.push({
          type: 'success',
          msg: `Categoria assegnata al tatami ${new Date().toLocaleTimeString()})`,
          timeout: 5000
        });
        if(this.primaryModal)
          this.primaryModal.hide();

        this.getCategories();
      }else{
        this.alertsDismiss.push({
          type: 'error',
          msg: `Operazione non andata a buon fine`,
          timeout: 5000
        });

      }
      
    });
  }

 
  //====================================================================================================
  // Lista atleti
  //====================================================================================================
  
  /**
   * Sintetico categoria
   * @param category 
   */
  openCategory(category: { id: any; }){
    this.categoriesService.getAthleteList({categorycode_id: category.id, readonly: true}).subscribe((response: any) => {
      this.athleteList = response.result.data;
      this.selectedCategory = category;
    });
    if(this.listModal)
      this.listModal.show();
  }

  /**
   * Tabellone kumite
   * @param category 
   */
  openKumiteBrackets(category: { id: any; }){
    this.dettaglio = true;
    this.selectedCategory = category;
    this.categoriesService.getAthleteList({categorycode_id: category.id, readonly: true}).subscribe((response: any) => {
      this.athleteList = response.result.data;
      this.kumiteMatches = [];
      

      if(this.athleteList.length == 3){
        let firstMatch = [this.athleteList[0]['athlete']['cognome'] + ' ' + this.athleteList[0]['athlete']['nome'] + ' - ' + this.athleteList[0]['athlete']['club']['club_name'],
          this.athleteList[1]['athlete']['cognome'] + ' ' + this.athleteList[1]['athlete']['nome'] + ' - ' + this.athleteList[1]['athlete']['club']['club_name']
        ];
        let firstResult = [null, null];

        let secondMatch = [
          this.athleteList[0]['athlete']['cognome'] + ' ' + this.athleteList[0]['athlete']['nome'] + ' - ' + this.athleteList[0]['athlete']['club']['club_name'],
          this.athleteList[2]['athlete']['cognome'] + ' ' + this.athleteList[2]['athlete']['nome'] + ' - ' + this.athleteList[2]['athlete']['club']['club_name']
        ];
        let secondResult = [null, null];

        let thirdMatch = [
          this.athleteList[1]['athlete']['cognome'] + ' ' + this.athleteList[1]['athlete']['nome'] + ' - ' + this.athleteList[1]['athlete']['club']['club_name'],
          this.athleteList[2]['athlete']['cognome'] + ' ' + this.athleteList[2]['athlete']['nome'] + ' - ' + this.athleteList[2]['athlete']['club']['club_name']
        ];  
        
        let thirdResult = [null, null];
        

        console.log(firstMatch);
        console.log(secondMatch);
        console.log(thirdMatch);

        this.minimalData = {
          teams: [firstMatch],
          results: firstResult
        }

        this.secondGirone = {
          teams: [secondMatch],
          results: secondResult
        }

        this.thirdGirone = {
          teams: [thirdMatch],
          results: thirdResult
        }  

        function render_fn(container: { append: (arg0: string) => void; }, data: any, score: any, state: any) {
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
              container.append(data)
              return;
          }
        }

        jQuery('#minimal').show();
        jQuery('#girone-2').show();

        jQuery('#girone-3').show();

        jQuery('#minimal').bracket({
          teamWidth: 250,
          scoreWidth: 45,
          matchMargin: 70,
          roundMargin: 70,
          init: this.minimalData,
          decorator:{edit:edit_fn, render: render_fn},
        });

        function edit_fn(container: any, data: any, doneCb: (arg0: any) => void) {
          
          doneCb(data);
        }
        

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
        let position = 1;
        let dataset:Tabset;
        
        console.log(this.athleteList.length);
        switch (true) {
          case (this.athleteList.length == 2):
            dataset = Dataset.dataset2;
            break;
          case (this.athleteList.length == 4):
            dataset = Dataset.dataset4;
            break;
          case (this.athleteList.length <= 8):
            dataset = Dataset.dataset8;
            break;
          case (this.athleteList.length <= 16):
            dataset = Dataset.dataset16;
            break;
          case (this.athleteList.length <= 32):
            dataset = Dataset.dataset32;
            break;
          default:
            dataset = Dataset.dataset8;
            console.log('ERROREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE');
            break;
        }
  
        while (position <= dataset.number) {
          let index = position-1;
          if(this.athleteList[index]){
            this.kumiteMatches.push({
              position: position, 
              id: this.athleteList[index].id, 
              name: this.athleteList[index].athlete.cognome + ' ' + this.athleteList[index].athlete.nome, 
              club:this.athleteList[index].athlete.club.club_name
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
      
        this.dettaglio = true;

        process(datasetTemp).then((data) =>
          {
              let teams: (string | null)[][] = [];
              let results = [];
              results.push([]);
  
              data.matches.forEach(match => {
  
                if(match.round_id == 0){

                  let opponent1 = null;
                  let opponent2 = null;
                  let result = [null,null];
    
                  if(match.opponent1 && match.opponent1.id){
                    let firstAthlete = this.kumiteMatches.find(x => x.id == match.opponent1.id);
                    opponent1 = firstAthlete.name + " </br> <p style= 'font-size:11px; font-style: italic;'>" + firstAthlete.club +"</p>";
                  }
    
                  if(match.opponent2 && match.opponent2.id){
                    let firstAthlete = this.kumiteMatches.find(x => x.id == match.opponent2.id);
                    opponent2 = firstAthlete.name + " </br> <p style= 'font-size:11px; font-style: italic;'>" + firstAthlete.club +"</p>";
                  }
    
                  teams.push([opponent1, opponent2]);
                  results[0].push(result);

                }       
                
              });
              console.log(teams);
  
              function render_fn(container: { append: (arg0: string) => void; }, data: any, score: any, state: any) {
                switch(state) {
                  case "empty-bye":
                    container.append("--")
                    return;
                  case "empty-tbd":
                    container.append("--")
                    return;
               
                  case "entry-no-score":
                  case "entry-default-win":
                  case "entry-complete":
                    container.append(data)
                    return;
                }
              }
  
              function edit_fn(container: any, data: any, doneCb: any) {
                return;
              }
  
              var customData = {
                teams : teams,
                results : []
              }

              jQuery('#minimal').bracket({
                teamWidth: 200,
                teamHeight: 50,
                scoreWidth: 45,
                matchMargin: 70,
                roundMargin: 70,
                disableHighlight: true,
                skipConsolationRound: true,
                init: customData,
                decorator:{edit: edit_fn, render: render_fn}
            })
  
          }
        );
      }

    });
  }

  sortMarkets(array: any[], sortArray: string | any[]){
    return [...array].sort(
      (a, b) => sortArray.indexOf(a.position) - sortArray.indexOf(b.position)
    )
  }

  backTocategory(){
    this.dettaglio = false;
  }


  //====================================================================================================
  // Visualizza classifica
  //====================================================================================================

    public openRankingModal(category: { id: any; }){
    this.selectedCategory = category;

    console.log(this.selectedCategory);

    

    this.categoriesService.getFinalRanking({categorycode_id: category.id}).subscribe((response: any) => {
      if(response.result.success){
        this.rankingData = response.result.data;
        if(this.rankingModal)
          this.rankingModal.show();
      }
      
    });
  }


  //====================================================================================================
  // Gestore Cards
  //====================================================================================================
  public openMenuAssign(event: { target: { parentNode: any; }; }){
    let parent = event.target.parentNode;
    while (parent){
      parent = parent.parentNode;
      if(parent.classList.contains('content')){
        parent.classList.add('flipped');
        parent.parentNode.parentNode.classList.add('z-index-1');
        break;
      }
    }
  }

  public closeMenuAssign(event: { target: { parentNode: any; }; }){
    let parent = event.target.parentNode;
    while (parent){
      parent = parent.parentNode;
      if(parent.classList.contains('content')){
        parent.classList.remove('flipped');
        parent.parentNode.parentNode.classList.remove('z-index-1');
        break;
      }
    }
  }

  openClassificaModal() {
    // Mostra un loading se vuoi, poi chiama il backend
    this.categoriesService.getClassificaSocieta().subscribe(
      (data) => {
        this.rankingList = data;
        console.log(this.rankingList);
        // Apre il modale Bootstrap
        if(this.modalClassifica)
          this.modalClassifica.show();
      },
      (error) => {
        console.error("Errore recupero classifica", error);
        alert("Impossibile caricare la classifica al momento.");
      }
    );
  }

}
