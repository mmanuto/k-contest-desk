import { Component, OnInit, ViewEncapsulation, ViewChild } from '@angular/core';
import { AdminPanelService } from './admin-panel.service';

import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { CategoriesService } from '../categories/categories.service';
import * as Dataset from '../tatami/datasets';
import { InMemoryDatabase } from '../tatami/storage/memory';
import { BracketsManager } from 'brackets-manager';
import { ToastrService } from 'ngx-toastr';


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
        console.log(response.result.data);
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

    let payload = {
      categoryId: null
    }
    this.adminPanelService.printBracketPdf(payload).subscribe((response:any) => {
      // 1. Crea un oggetto Blob con i dati ricevuti
            // response.body contiene i dati binari puri
            const blob = new Blob([response.body], { type: 'application/pdf' });

            // 2. Crea un URL temporaneo per il browser
            const url = window.URL.createObjectURL(blob);

            // 3. Crea un link <a> invisibile nel DOM
            const link = document.createElement('a');
            link.href = url;
            
            // 4. Imposta il nome del file (puoi prenderlo dagli header o metterne uno fisso)
            link.download = payload.categoryId ? 'Tabellone_Singolo.pdf' : 'Tutti_Tabelloni.pdf';
            
            // 5. Simula il click e rimuovi il link
            link.click();
            window.URL.revokeObjectURL(url); // Libera memoria
    });
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


  // ========================================================================
  // STAMPA CARTELLINI
  // ========================================================================
  downloadAllPassesZip(){
    this.adminPanelService.downloadPassesZip().subscribe({
      next: (blob: Blob) => {
        // 1. Creiamo un oggetto URL per il file ricevuto
        const url = window.URL.createObjectURL(blob);

        // 2. Creiamo un link <a> invisibile
        const link = document.createElement('a');
        link.href = url;

        // 3. Impostiamo il nome del file che verrà salvato sul PC
        link.download = 'Tutti_Pass_Atleti.zip';

        // 4. Simuliamo il click e poi rimuoviamo il link
        link.click();
        
        // 5. Pulizia memoria (importante per non intasare il browser)
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        console.error('Errore durante il download dello ZIP', err);
        alert('Impossibile scaricare i pass. Verifica che ci siano iscritti.');
      }
    });
  }

}
