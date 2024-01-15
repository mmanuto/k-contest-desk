import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import * as Constants from '../../constants';
import { Observable } from 'rxjs';


@Injectable()
export class AdminPanelService {

    constructor(public http:HttpClient) { }


    getCompetitionData(){
        return this.http.post(Constants.BASEAPPURL + 'competitions/getCompetitionData', {});

    }

    getCategories()
     {
        console.log('entra');
         return this.http.post(Constants.BASEAPPURL + 'categorycodes/getCategories', {});
                 
    }

    getCategoriesWithAthletes(){
        return this.http.post(Constants.BASEAPPURL + 'categorycodes/getCategoriesWithAthletes', {});
    }

    getAthleteList(request): Observable<any>{
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getAthleteListSintetico', request);
    }

    syncCompetitionData(){
        return this.http.post(Constants.ISCRIZIONI_URL + 'competitions/syncCompetitionData', {competition_id:10});
    }

    importCompetitionData(request){
        return this.http.post(Constants.BASEAPPURL + 'competitions/importCompetitionData', request);
    }

    getModifiedInscriptions(){
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getModifiedInscriptions', {});
    }

    updateCompetitionData(request){
        return this.http.post(Constants.ISCRIZIONI_URL + 'athletesInscriptions/updateCompetitionData', request);
    }

    //--------------------------------------------------- PRINT KUMITE ------------------------------------------------------------------

    getKumiteList(){
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getKumiteList', {});
    }
} 