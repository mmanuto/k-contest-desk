import { Component, OnInit, ViewEncapsulation, ViewChild } from '@angular/core';
import { AdminPanelService } from './admin-panel.service';

import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { CategoriesService } from '../categories/categories.service';
import * as Dataset from '../tatami/datasets';
import { InMemoryDatabase } from '../tatami/storage/memory';
import { BracketsManager } from 'brackets-manager';
import { ToastrService } from 'ngx-toastr';

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
  selector: 'admin-panel',
  templateUrl: './admin-panel.component.html',
  encapsulation: ViewEncapsulation.None
})
export class AdminPanleComponent implements OnInit {

  @ViewChild('download', {read: HTMLElement})
  download: HTMLElement;

  competitionData;
  tabelloni = new jsPDF("p", "mm", "a4");
  frontespizi = new jsPDF("p", "mm", "a4");
  matchList;
  gironiList;

  constructor(public adminPanelService: AdminPanelService,
    public toastrService: ToastrService, 
    public categoriesService: CategoriesService) { }

  ngOnInit() {
    this.adminPanelService.getCompetitionData().subscribe((response:any) => {
      this.competitionData = response.result.data;
    });
  }

  //------------------------------------------------------- SCAMBIO DATI ----------------------------------------------------------------------------------

  
  syncCompetitionData(){
    this.adminPanelService.syncCompetitionData().subscribe((response:any) => {
      if(response.result.success){
        
        this.adminPanelService.importCompetitionData(response.result.data).subscribe((response:any) => {

          if(response.result.success){
            this.toastrService.success('Caricamento completato', 'Toastr fun!');
          }
        });
      }
    });
  }

  updateCompetitionData(){
    this.adminPanelService.getModifiedInscriptions().subscribe((response:any) => {
      if(response.result.success && response.result.data.length > 0){
        this.adminPanelService.updateCompetitionData(response.result.data).subscribe((responseUpdate:any) =>{

        });
      }
    });
  }

  // ======================================================================== FRONTESPIZI =========================================================================

  generaFrontespizi(){

    this.frontespizi = new jsPDF("p", "mm", "a4");
    this.adminPanelService.getCategories().subscribe((response: any) => {
      let categories = response.result.data;

      let countCategories = categories.length;
      let countPdf = 0;

      categories.forEach(category => {
          this.creaPdfFrontespizi(category);
          countPdf++;
      });

      let loopLoading = true;
      
      while(loopLoading){
        if(countPdf == countCategories){
          loopLoading = false;
          this.frontespizi.save('Frontespizi.pdf');
        }
      }

    });

  }

  // ======================================================================== SINTETICO CATEGORIA ==============================================================

  generaSinteticoCategoria(kumite = false){

    this.adminPanelService.getCategoriesWithAthletes().subscribe((response:any) => {
      let categories = response.result.data;

      this.tabelloni = new jsPDF("p", "mm", "a4");
      let countCategories = categories.length;
      let countPdf = 0;

      categories.forEach(category => {

        if(kumite && (category.id.substring(0, 3) == 'KUA' || category.id.substring(0, 3) == 'KUG')){
          this.creaPdfIscrizioni(category);
        }else if (!kumite){
          this.creaPdfIscrizioni(category, true);
        }
        
        countPdf++;
      });

      let loopLoading = true;
      while(loopLoading){
        if(countPdf == countCategories){
          loopLoading = false;
          let filename = kumite ? 'Kumite.pdf' : 'Sintetico_Categorie.pdf';
          this.tabelloni.save(filename);
        }
      }

    });   
    
  }

  
  creaPdfIscrizioni(category, peso = false) {

    console.log(this.competitionData);
    
    let nomeGara = this.competitionData.nome_gara;
    
    let lMargin = 15; //left margin in mm
    let pdfInMM = 210;  // width of A4 in mm
    let pageCenter = pdfInMM / 2;

    let img = new Image();
    img.src = "assets/img/logo/LogoCSEN.png";
    
    let imgData = this.getBase64Image(img);

    this.tabelloni.setFont("times");
    this.tabelloni.addImage(imgData, 'PNG', lMargin, 13, 28, 28);
    this.tabelloni.addImage(imgData, 'PNG', 155, 13, 28, 28);

    this.tabelloni.setFontSize(22);
    this.tabelloni.text(nomeGara, pageCenter, 35, {align: 'center'});
  
    var categoryRows = [
      ['Specialità', category.specialita],
      ['Categoria', category.categoria],
      ['Cintura', category.grado],
      ['Sesso', category.sesso],
      ['Peso', category.cat_peso]
    ];

    autoTable(this.tabelloni, {
      theme: 'grid',
      margin: { top: 60, right: 50, left: 50 },
      body: categoryRows,
      styles: { cellPadding: 0.5, fontSize: 9 }
    });


    var columns = [
      {header: "Nr.", dataKey: 'nr'}, 
      {header: "Atleti", dataKey: 'atleti'}, 
      {header: "Società", dataKey: 'societa'}
    ];
 
    if(category.id.substring(0, 3) == 'KIA'){
      columns = [
        {header: "Nr.", dataKey: 'nr'}, 
        {header: "Atleti", dataKey: 'atleti'}, 
        {header: "Società", dataKey: 'societa'}
      ];
    }

    if((category.id.substring(0, 3) == 'KUA' || category.id.substring(0, 3) == 'KUG') && peso){
      columns = [
        {header: "Nr.", dataKey: 'nr'},  
        {header: "Atleti", dataKey: 'atleti'}, 
        {header: "Società", dataKey: 'societa'},
        {header: "Peso", dataKey: 'nr'}
      ];
    }

    var rows = [];
    let count = 1;
    for (let atleta of category.athletes) {

      let record = [count, atleta.athlete.cognome + ' ' + atleta.athlete.nome, atleta.athlete.club.club_name];

      if((category.id.substring(0, 3) == 'KUA' || category.id.substring(0, 3) == 'KUG') && peso){
        
        record = [count, atleta.athlete.cognome + ' ' + atleta.athlete.nome, atleta.athlete.club.club_name, atleta.athlete.peso];
      }
      rows.push(record);
      count++;
    }

    autoTable(this.tabelloni, {
      theme: 'grid',
      margin: {right: 15, left: 15 },
      columnStyles: { nr: { halign: 'center'}},
      columns: columns,
      body: rows,
      styles: { cellPadding: 1, fontSize: 10 }
    });

    this.tabelloni.addPage();
  }

  creaPdfFrontespizi(category) {

    let nomeGara = this.competitionData.nome_gara;
    let dataGara = this.competitionData.data_gara.split('T')[0].split('-');
    //let imgData = this.getBase64ImageFromURL("assets/img/logo/LogoCSEN.png");

    var lMargin = 15; //left margin in mm
    var rMargin = 15; //right margin in mm
    var pdfInMM = 210;  // width of A4 in mm
    var pageCenter = pdfInMM / 2;


    let img = new Image();
    img.src = "assets/img/logo/LogoCSEN.png";
    let imgData = this.getBase64Image(img);

    this.frontespizi.setFont("times");
    this.frontespizi.addImage(imgData, 'PNG', pageCenter - 22, 20, 45, 45);
    this.frontespizi.setFontSize(20);
    this.frontespizi.setFont('Calibri', 'bold');

    this.frontespizi.text(nomeGara, pageCenter, 77, {align: 'center'});
    this.frontespizi.text(dataGara[2]+'/'+dataGara[1]+'/'+dataGara[0], pageCenter, 87, {align: 'center'});

    this.frontespizi.setFontSize(18);

    let sesso = category.sesso;
    if(category.cat_peso){
      sesso += ' ' + category.cat_peso;
    }

    this.frontespizi.text(category.specialita.toUpperCase(), pageCenter, 110, {align: 'center'});
    this.frontespizi.text(category.categoria.toUpperCase(), pageCenter, 120, {align: 'center'});
    this.frontespizi.text(sesso.toUpperCase(), pageCenter, 130, {align: 'center'});
    this.frontespizi.text(category.grado.toUpperCase(), pageCenter, 140, {align: 'center'});

    this.frontespizi.setFontSize(11);
    this.frontespizi.setFont('Calibri', 'normal');

    this.frontespizi.text('CLASSIFICA:', lMargin, 170);
    this.frontespizi.text('1°   …………………………………………………………………………………..', lMargin, 179);
    this.frontespizi.text('2°   …………………………………………………………………………………..', lMargin, 187);
    this.frontespizi.text('3°   …………………………………………………………………………………..', lMargin, 195);
    this.frontespizi.text('3°   …………………………………………………………………………………..', lMargin, 203);
    this.frontespizi.text('PRESIDENTI DI GIURIA:', lMargin, 212);
    this.frontespizi.text('………………………………………………………………………………………………………….', lMargin, 220);
    this.frontespizi.text('UFFICIALI DI GARA:', lMargin, 228);
    this.frontespizi.text('………………………………………………………………………………………………………….', lMargin, 238);
    this.frontespizi.text('………………………………………………………………………………………………………….', lMargin, 248);
    this.frontespizi.text('………………………………………………………………………………………………………….', lMargin, 258);


    this.frontespizi.addPage();
  }

  getBase64Image(img: HTMLImageElement) {
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    return canvas.toDataURL("image/png");
  }

  //------------------------------------------------------------------ KUMITE -----------------------------------------------------------------------------------
  
 
  generaTabKumite(){
    this.matchList = [];
    this.gironiList = [];

    this.adminPanelService.getKumiteList().subscribe((response:any) =>{
      console.log(response.result.data);

      if(response.result.success){
        console.log(response.result.data.length);
        this.tabelloni = new jsPDF("p", "mm", "a4");
        let countCategories = response.result.data.length;
        let countPdf = 0;

        response.result.data.forEach(element => {
          
          let nomeGara = this.competitionData.nome_gara;
    
          let lMargin = 15; //left margin in mm
          let pdfInMM = 210;  // width of A4 in mm
          let pageCenter = pdfInMM / 2;

          let img = new Image();
          img.src = "assets/img/logo/LogoCSEN.png";
          
          let imgData = this.getBase64Image(img);

          this.tabelloni.setFont("times");
          this.tabelloni.addImage(imgData, 'PNG', lMargin, 13, 28, 28);
          this.tabelloni.addImage(imgData, 'PNG', 155, 13, 28, 28);

          this.tabelloni.setFontSize(22);
          this.tabelloni.text(nomeGara, pageCenter, 35, {align: 'center'});

          if(element.athleteList.length == 3){

            this.gironeItaliana(element.athleteList, countPdf);    
    
          }else{
            
            this.generateBrackets(element.athleteList, countPdf);
            
          }

          countPdf++;
          console.log(countPdf);
        });

        let loopLoading = true;
      
     while(loopLoading){
        if(countPdf == countCategories){
          loopLoading = false;
          this.tabelloni.save('Kumite.pdf');
        }
      }


      }

      /*
      */
      
    })

  }

  gironeItaliana(kumiteAthleteList, countPdf){

    let firstMatch = [
      {name: kumiteAthleteList[0]['athlete']['cognome'] + ' ' + kumiteAthleteList[0]['athlete']['nome'], id: kumiteAthleteList[0]['id'], round: 1},
      {name: kumiteAthleteList[1]['athlete']['cognome'] + ' ' + kumiteAthleteList[1]['athlete']['nome'], id: kumiteAthleteList[1]['id'], round: 1}
    ];
    let firstResult = [null, null];

    let secondMatch = [
      {name: kumiteAthleteList[0]['athlete']['cognome'] + ' ' + kumiteAthleteList[0]['athlete']['nome'], id: kumiteAthleteList[0]['id'], round: 2},
      {name: kumiteAthleteList[2]['athlete']['cognome'] + ' ' + kumiteAthleteList[2]['athlete']['nome'], id: kumiteAthleteList[2]['id'], round: 2}
    ];
    let secondResult = [null, null];

    let thirdMatch = [
      {name: kumiteAthleteList[1]['athlete']['cognome'] + ' ' + kumiteAthleteList[1]['athlete']['nome'], id: kumiteAthleteList[1]['id'], round: 3},
      {name: kumiteAthleteList[2]['athlete']['cognome'] + ' ' + kumiteAthleteList[2]['athlete']['nome'], id: kumiteAthleteList[2]['id'], round: 3}
    ];  
    
    let thirdResult = [null, null];

    let minimalData = {
        teams: [firstMatch],
        results: firstResult
    }

    let secondGirone = {
      teams: [secondMatch],
      results: secondResult
    }

    let thirdGirone = {
      teams: [thirdMatch],
      results: thirdResult
    }           

      
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
          container.append(data.name)
          return;
      }
    }

    /* Edit function is called when team label is clicked */
    function edit_fn(container, data, doneCb) {
      doneCb(data);
    }

    let match_a_name = "match-a-"+countPdf;
    let match_b_name = "match-b-"+countPdf;
    let match_c_name = "match-c-"+countPdf;
    let match_container_name = "match-cont-"+countPdf;

    let match_a = this.createElement("div",{"id":match_a_name},"");
    let match_b = this.createElement("div",{"id":match_b_name},"");
    let match_c = this.createElement("div",{"id":match_c_name},"");
    let match_container = this.createElement("div",{"id":match_container_name},[match_a,match_b,match_c]);
    document.body.appendChild(match_container);

      jQuery('#'+match_a_name).bracket({
            teamWidth: 250,
            scoreWidth: 45,
            matchMargin: 70,
            roundMargin: 70,
            init: minimalData,
            save: function(){},
            decorator:{edit:edit_fn, render: render_fn},
        });

        jQuery('#'+match_b_name).bracket({
          teamWidth: 250,
          scoreWidth: 45,
          matchMargin: 70,
          roundMargin: 70,
          init: secondGirone,
          save: function(){},
          decorator:{edit:edit_fn, render: render_fn},
      });

      jQuery('#'+match_c_name).bracket({
        teamWidth: 250,
        scoreWidth: 45,
        matchMargin: 70,
        roundMargin: 70,
        init: thirdGirone,
        save: function(){},
        decorator:{edit:edit_fn, render: render_fn},
    });

    let elementHTML = document.getElementById(match_container);

    this.tabelloni.html(elementHTML, {
        callback: function(doc) {
            // Save the PDF
            this.tabelloni.addPage();
        },
        x: 15,
        y: 15,
        width: 170, //target width in the PDF document
        windowWidth: 650 //window width in CSS pixels
    });      
  }

  generateBrackets(kumiteAthleteList, countPdf){
    let position = 1;
        let dataset:Tabset = null;
        let kumiteMatches = [];

        switch (true) {
          case (kumiteAthleteList.length == 2):
            dataset = Dataset.dataset2;
            break;
          case (kumiteAthleteList.length == 4):
            dataset = Dataset.dataset4;
            break;
          case (kumiteAthleteList.length <= 8):
            dataset = Dataset.dataset8;
            break;
          case (kumiteAthleteList.length <= 16):
            dataset = Dataset.dataset16;
            break;
          case (kumiteAthleteList.length <= 32):
            dataset = Dataset.dataset32;
            break;
          default:
            console.log('ERROREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE');
            break;
        }

        while (position <= dataset.number) {
          let index = position-1;
          if(kumiteAthleteList[index]){
            kumiteMatches.push({
              position: position, 
              id: kumiteAthleteList[index].id, 
              name: kumiteAthleteList[index].athlete.cognome + ' ' + kumiteAthleteList[index].athlete.nome, 
              club:kumiteAthleteList[index].athlete.club.club_name
            })
          }else{
            kumiteMatches.push({
              position: position, 
              id: null, 
              name: null, 
              club: null
            })
          }
          
          position++;
        }

        kumiteMatches = this.sortMarkets(kumiteMatches, dataset.positions);

        const datasetTemp: Dataset = {
          title: 'Kumite',
          type: 'single_elimination',
          roster: kumiteMatches,
        };
        console.log(kumiteMatches);

        
        process(datasetTemp).then((data) => 
        {

          let teams = [];
          let results = [];
          results.push([]);
          //console.log(data);
          data.matches.forEach(match => {
            
            if(match.round_id == 0){

              let opponent1 = null;
                  let opponent2 = null;
                  let result = [null,null];
    
                  if(match.opponent1 && match.opponent1.id){
                    let firstAthlete = kumiteMatches.find(x => x.id == match.opponent1.id);
                    opponent1 = firstAthlete.name + ' - ' + firstAthlete.club;
                  }
    
                  if(match.opponent2 && match.opponent2.id){
                    let firstAthlete = kumiteMatches.find(x => x.id == match.opponent2.id);
                    opponent2 = firstAthlete.name + ' - ' + firstAthlete.club;
                  }
    
                  teams.push([opponent1, opponent2]);
                  results[0].push(result);
            }
            
          });
        

          var customData = {
            teams : teams,
            results : []
          }
                
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
          container.append(data.name)
          return;
      }
    }

    // Edit function is called when team label is clicked
    function edit_fn(container, data, doneCb) {
      doneCb(data);
    }

    let match_a_name = "match-a-"+countPdf;
    let match_container_name = "match-cont-"+countPdf;

    let match_a = this.createElement("div",{"id":match_a_name},"");
    let match_container = this.createElement("div",{"id":match_container_name},[match_a]);
    document.body.appendChild(match_container);



      jQuery('#'+match_a_name).bracket({
            teamWidth: 200,
            scoreWidth: 45,
            matchMargin: 70,
            roundMargin: 70,
            init: customData,
            save: function(){},
            decorator:{edit:edit_fn, render: render_fn},
        })

        let elementHTML = document.getElementById(match_container);

        this.tabelloni.html(elementHTML, {
            callback: function(doc) {
                // Save the PDF
                this.tabelloni.addPage();
            },
            x: 15,
            y: 15,
            width: 170, //target width in the PDF document
            windowWidth: 650 //window width in CSS pixels
        });      
  
        });
  }

  sortMarkets(array, sortArray){
    return [...array].sort(
      (a, b) => sortArray.indexOf(a.position) - sortArray.indexOf(b.position)
    )
  }

  createElement(element, attribute, inner) {
    if (typeof(element) === "undefined") {
      return false;
    }
    if (typeof(inner) === "undefined") {
      inner = "";
    }
    var el = document.createElement(element);
    if (typeof(attribute) === 'object') {
      for (var key in attribute) {
        el.setAttribute(key, attribute[key]);
      }
    }
    if (!Array.isArray(inner)) {
      inner = [inner];
    }
    for (var k = 0; k < inner.length; k++) {
      if (inner[k].tagName) {
        el.appendChild(inner[k]);
      } else {
        el.appendChild(document.createTextNode(inner[k]));
      }
    }
    return el;
  }

}
