import { Component, OnInit, ViewEncapsulation, ViewChild } from '@angular/core';
import { AdminPanelService } from './admin-panel.service';
import { CategoriesService } from '../categories/categories.service';
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

  constructor(public adminPanelService: AdminPanelService,
    public toastrService: ToastrService, 
    public categoriesService: CategoriesService) { }

  ngOnInit() {
    this.adminPanelService.getCompetitionData().subscribe((response:any) => {
      this.competitionData = response.result.data;
      console.log(this.competitionData);
    });
  }

  //------------------------------------------------------- SCAMBIO DATI ----------------------------------------------------------------------------------

  
  syncCompetitionData(){
    this.adminPanelService.syncCompetitionData(this.competitionData.id).subscribe((response:any) => {
      if(response.result.success){
        this.adminPanelService.importCompetitionData(response.result.data).subscribe((response:any) => {

          if(response.result.success){
            this.toastrService.success('Caricamento completato', 'Toastr fun!');
          }
        });
      }
    });
  }

  // ======================================================================== FRONTESPIZI =========================================================================

  generaFrontespizi(){

    this.adminPanelService.generaFrontespizi({competitionId: this.competitionData.id}).subscribe(blob => {
      // Crea link per il download
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'Frontespizi.pdf';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      // Nascondi loader...
  }, error => {
      console.error('Errore stampa frontespizi', error);
  });

  }

  // ======================================================================== SINTETICO CATEGORIA ==============================================================

  generaSinteticoCategoria(){

    this.adminPanelService.generaSinteticoCategoria({nomeGara: this.competitionData.nome_gara}).subscribe(blob => {
      // Crea un link temporaneo per scaricare il file
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'Sintetico_Categorie.pdf';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      // Nascondi loader...
    }, error => {
        console.error('Errore stampa', error);
    });


    
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
