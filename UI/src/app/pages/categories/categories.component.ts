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

import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import * as Dataset from '../tatami/datasets';
import { InMemoryDatabase } from '../tatami/storage/memory';
import { BracketsManager } from 'brackets-manager';
import html2canvas from 'html2canvas';
 
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
  selector: 'app-membership',
  templateUrl: './categories.component.html',
  styleUrls: ['./categories.component.scss'],
  encapsulation: ViewEncapsulation.None,
  providers: [ CategoriesService, MenuService ]
})
export class CategoriesComponent implements OnInit {

  @ViewChild('primaryModal') public primaryModal: ModalDirective;
  @ViewChild('listModal') public listModal: ModalDirective;
  @ViewChild('rankingModal') public rankingModal: ModalDirective;

  public searchText: string;
  public p:any;
  public type:string = 'list';
  

  categories: any[];
  athleteList: any[];
  tatamiList: any[];
  tatamiStatus: any[];
  public athletestNumber = null;
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

  public toggle(type){
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

  splitCategory(category){
    console.log(category);
    if (window.confirm(`Proseguendo con l'operazione, la categoria ${category.specialita} - ${category.categoria} - ${category.grado} ${category.sesso} verrà divisa in Maschile e Femminile. Continuare?`)){
      this.categoriesService.splitCategory(category).subscribe((response: any) => {

      });
      
    }
  }

  //====================================================================================================
  // Assegna a tatami
  //====================================================================================================
  openTatamiModal(category_id){
    this.message = '';
    this.getTatamiStatus();

    this.tatamiForm = this.fb.group({
      'user_id': [''],
      'categorycode_id': [category_id]
    });

    this.tatamiStatus.forEach(tatami => {
      tatami.userCategories.forEach(category => {
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

    
    this.primaryModal.show();
  }

  assignTatami(){
    let request = {
      user_id: this.tatamiForm.value.user_id,
      categorycode_id: this.tatamiForm.value.categorycode_id,
      status: categoryStatus.TODO
    }

    this.categoriesService.saveTatamiAssegnee(request).subscribe((response: any) => {

      if(response.result.success){
        this.alertsDismiss.push({
          type: 'success',
          msg: `Categoria assegnata al tatami ${new Date().toLocaleTimeString()})`,
          timeout: 5000
        });
        this.primaryModal.hide();
        console.log('ANDATA');
        this.getCategories();
      }else{
        this.alertsDismiss.push({
          type: 'error',
          msg: `Operazione non andata a buon fine`,
          timeout: 5000
        });
        console.log('ERROR');
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
  openCategory(category){
    this.categoriesService.getAthleteList({categorycode_id: category.id, readonly: true}).subscribe((response: any) => {
      this.athleteList = response.result.data;
      this.selectedCategory = category;
    });

    this.listModal.show();
  }

  /**
   * Tabellone kumite
   * @param category 
   */
  openKumiteBrackets(category){
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

        function edit_fn(container, data, doneCb) {
          
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
        let dataset:Tabset = null;
        
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
              let teams = [];
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
  
              function render_fn(container, data, score, state) {
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
  
              function edit_fn(container, data, doneCb) {
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

  sortMarkets(array, sortArray){
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

    public openRankingModal(category){
    this.selectedCategory = category;

    console.log(this.selectedCategory);

    

    this.categoriesService.getFinalRanking({categorycode_id: category.id}).subscribe((response: any) => {
      if(response.result.success){
        this.rankingData = response.result.data;
        this.rankingModal.show();
      }
      
    });
  }

  //====================================================================================================
  // Stampa pdf
  //====================================================================================================
  public async generatePdf(category){

    let tabelloni = new jsPDF("p", "mm", "a4");
    let nomeGara = 'Trofeo Gattamelata';
    
    let lMargin = 15; //left margin in mm
    let pdfInMM = 210;  // width of A4 in mm
    let pageCenter = pdfInMM / 2;

    let img = new Image();
    img.src = "assets/img/logo/LogoCSEN.png";
    
    let imgData = this.getBase64Image(img);
    
    tabelloni.setFont("times");
    tabelloni.addImage(imgData, 'PNG', lMargin, 13, 28, 28);
    tabelloni.addImage(imgData, 'PNG', 155, 13, 28, 28);

    tabelloni.setFontSize(22);
    tabelloni.text(nomeGara, pageCenter, 35, {align: 'center'});
    tabelloni.setFontSize(18);
    let categoria = this.selectedCategory.specialita + ' ' + this.selectedCategory.categoria 
    
    let categoria2 = this.selectedCategory.grado + this.selectedCategory.cat_peso + '- ' + this.selectedCategory.sesso;
    tabelloni.text(categoria, pageCenter, 70, {align: 'center'});
    tabelloni.text(categoria2, pageCenter, 75, {align: 'center'});
    let element = document.getElementById('kumite');
    const canvas = await html2canvas(element, {scale: 3});
    const imgDataKumite = canvas.toDataURL('image/png');

    const imgWidth = 190; // mm (A4 width)
    const imgHeight = (canvas.height * imgWidth) / canvas.width;

    tabelloni.addImage(imgDataKumite, 'PNG', 15, 100, imgWidth, imgHeight);
    
    tabelloni.save('Kumite.pdf');

      
  }

  getBase64Image(img: HTMLImageElement) {
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    return canvas.toDataURL("image/png");
  }

  //====================================================================================================
  // Gestore Cards
  //====================================================================================================
  public openMenuAssign(event){
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

  public closeMenuAssign(event){
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

}
