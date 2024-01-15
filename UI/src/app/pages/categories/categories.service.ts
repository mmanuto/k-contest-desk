import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import * as Constants from '../../constants';


@Injectable()
export class CategoriesService {
    public url = "api/users";
    constructor(public http:HttpClient) { }

    getCategories()
     {
        console.log('entra');
         return this.http.post(Constants.BASEAPPURL + 'categorycodes/getCategories', {});
                 
    }

    getCompetitions(){
        return this.http.post(Constants.ISCRIZIONI_URL + 'competitions/getCompetitions', {});

    }

    getTatami()
     {
         return this.http.post(Constants.BASEAPPURL + 'users/getTatami', {});
                 
    }

    getTatamistatus(){
        return this.http.post(Constants.BASEAPPURL + 'usersCategorycodes/getUserCategories', {});
    }

    saveTatamiAssegnee(request){
        return this.http.post(Constants.BASEAPPURL + 'usersCategorycodes/saveUserCategories', request);
    }

    getAthleteList(request){
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getAthletesByCategory', request);
    }

    getTotaleIscrizioni(){
        return this.http.post(Constants.BASEAPPURL + 'competitions/getTotaleIscrizioni', {});
    }
} 