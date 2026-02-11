import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';


@Injectable()
export class AdminPanelService {

    constructor(public http:HttpClient) { }


    getCompetitionData(){
        return this.http.post(environment.BASEAPPURL + 'competitions/getCompetitionData', {});

    }

    syncCompetitionData(){
        return this.http.post(environment.ISCRIZIONI_URL + 'competitions/syncCompetitionData', {competition_id:'GTTMLT-2026'});
    }

    importCompetitionData(request){
        return this.http.post(environment.BASEAPPURL + 'competitions/importCompetitionData', request);
    }

    getModifiedInscriptions(){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getModifiedInscriptions', {});
    }

    updateCompetitionData(request){
        return this.http.post(environment.ISCRIZIONI_URL + 'athletesInscriptions/updateCompetitionData', request);
    }

    //--------------------------------------------------- PRINT PDF ------------------------------------------------------------------

    generaSinteticoCategoria(request){
        return this.http.get(environment.BASEAPPURL + 'prints/downloadAthleteList', { 
            responseType: 'blob', // Fondamentale per i file binari
            params: request
        });
    }

    generaFrontespizi(request){
        return this.http.get(environment.BASEAPPURL + 'prints/downloadCoverPages', { 
            responseType: 'blob', // Fondamentale per i file binari
            params: request
        });
    }

    getKumiteList(){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getKumiteList', {});
    }

    printBracketPdf(request){
        return this.http.post(environment.BASEAPPURL + 'prints/printBracketPdf', request, {
            responseType: 'blob' as 'json', // "as json" è un trucco per TypeScript, ma a runtime passa 'blob'
            observe: 'response' // Utile per leggere eventuali header se servono
        });
    }

    downloadPassesZip(): Observable<Blob> {
        return this.http.post(`${environment.BASEAPPURL}prints/downloadAllPassesZip`, {}, {
        responseType: 'blob' // Dice ad Angular che ci aspettiamo un file binario
        });
    }
} 