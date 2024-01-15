import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ActivatedRouteSnapshot, Resolve, RouterStateSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import * as Constants from '../../constants';

@Injectable()
export class TatamiService
{
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }

    getTatamiStatus(request){
        return this.http.post(Constants.BASEAPPURL + 'usersCategorycodes/getUserCategories', request);
    }

    getAthleteList(request){
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getAthletesByCategory', request);
    }

    getKataList(){
        return this.http.post(Constants.BASEAPPURL + 'usersCategorycodes/getKataList', {});
    }

    saveScoresAndGetTotals(request){
        return this.http.post(Constants.BASEAPPURL + 'scores/saveScoresAndGetTotals', request);
    }

    elaboraPunteggi(request){
        return this.http.post(Constants.BASEAPPURL + 'scores/elaboraPunteggi', request);
    }

    updateCategoryStatus(request){
        return this.http.post(Constants.BASEAPPURL + 'usersCategorycodes/updateCategoryStatus', request);
    }

    getPreviousMatch(request){
        return this.http.post(Constants.BASEAPPURL + 'scores/getPreviousMatch', request);
    }

    matchAthletes(request){
        return this.http.post(Constants.BASEAPPURL + 'scores/matchAthletes', request);
    }

    saveAthleteScores(request){
        return this.http.post(Constants.BASEAPPURL + 'scores/saveAthleteScores', request);
    }

}
