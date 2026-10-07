import { Component, OnInit, ViewChild } from '@angular/core';
import { AdminPanelService } from './admin-panel.service';
import { CategoriesService } from '../categories/categories.service';
import { ToastrService } from 'ngx-toastr';


@Component({
  selector: 'admin-panel',
  templateUrl: './admin-panel.component.html',
  styleUrls: ['./admin-panel.component.scss']
})
export class AdminPanleComponent implements OnInit {

  @ViewChild('download', {read: HTMLElement})
  download: HTMLElement;

  competitionData;
  onlineCompetitions: any[] = [];
  selectedCompetitionId = '';
  isLoadingCompetitions = false;
  isSyncing = false;

  constructor(public adminPanelService: AdminPanelService,
    public toastrService: ToastrService, 
    public categoriesService: CategoriesService) { }

  ngOnInit() {
    this.loadLocalCompetition();
    this.loadOnlineCompetitions();
  }

  loadLocalCompetition(){
    this.adminPanelService.getCompetitionData().subscribe((response:any) => {
      this.competitionData = response.result.data;
      if (this.competitionData && !this.selectedCompetitionId) {
        this.selectedCompetitionId = this.competitionData.id;
      }
    });
  }

  loadOnlineCompetitions(){
    this.isLoadingCompetitions = true;

    this.adminPanelService.getOnlineCompetitions().subscribe({
      next: (response:any) => {
        this.onlineCompetitions = response.result.data || [];
        this.isLoadingCompetitions = false;
      },
      error: () => {
        this.onlineCompetitions = [];
        this.isLoadingCompetitions = false;
        this.toastrService.error(
          'Impossibile recuperare l\'elenco delle gare dal portale online.',
          'Errore di connessione'
        );
      }
    });
  }

  //------------------------------------------------------- SCAMBIO DATI ----------------------------------------------------------------------------------

  
  syncCompetitionData(){
    if (!this.selectedCompetitionId) {
      this.toastrService.warning('Seleziona prima la gara da sincronizzare.', 'Gara non selezionata');
      return;
    }

    const selectedCompetition = this.onlineCompetitions.find(
      competition => competition.id === this.selectedCompetitionId
    );
    const competitionName = selectedCompetition
      ? selectedCompetition.nome_gara
      : this.selectedCompetitionId;

    if (!window.confirm(
      `Sincronizzare "${competitionName}"? I dati della gara attualmente presenti sul PC verranno sostituiti.`
    )) {
      return;
    }

    this.isSyncing = true;

    this.adminPanelService.syncCompetitionData(this.selectedCompetitionId).subscribe({
      next: (response:any) => {
        if (!response.result.success) {
          this.isSyncing = false;
          this.toastrService.error('Il portale non ha restituito i dati della gara.', 'Sincronizzazione non riuscita');
          return;
        }

        this.adminPanelService.importCompetitionData(response.result.data).subscribe({
          next: (importResponse:any) => {
            this.isSyncing = false;

            if(importResponse.result.success){
              this.loadLocalCompetition();
              this.toastrService.success('Caricamento completato', 'Sincronizzazione completata');
            } else {
              this.toastrService.error(
                importResponse.result.message || 'Non è stato possibile importare i dati.',
                'Importazione non riuscita'
              );
            }
          },
          error: () => {
            this.isSyncing = false;
            this.toastrService.error('Errore durante l\'importazione locale dei dati.', 'Importazione non riuscita');
          }
        });
      },
      error: () => {
        this.isSyncing = false;
        this.toastrService.error('Errore durante il download dei dati dal portale.', 'Sincronizzazione non riuscita');
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
