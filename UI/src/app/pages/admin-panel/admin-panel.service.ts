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

    getCategories()
     {
        console.log('entra');
         return this.http.post(environment.BASEAPPURL + 'categorycodes/getCategories', {});
                 
    }

    getCategoriesWithAthletes(){
        return this.http.post(environment.BASEAPPURL + 'categorycodes/getCategoriesWithAthletes', {});
    }

    getAthleteList(request){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getAthleteListSintetico', request);
    }

    syncCompetitionData(){
        return this.http.post(environment.ISCRIZIONI_URL + 'competitions/syncCompetitionData', {competition_id:'GTTMLT-2025'});
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

    //--------------------------------------------------- PRINT KUMITE ------------------------------------------------------------------

    getKumiteList(){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getKumiteList', {});
    }
} 