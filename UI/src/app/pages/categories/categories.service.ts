import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';


@Injectable()
export class CategoriesService {
    public url = "api/users";
    constructor(public http:HttpClient) { }

    getCategories()
     {
        console.log('entra');
         return this.http.post(environment.BASEAPPURL + 'categorycodes/getCategories', {});
                 
    }

    getCompetitions(){
        return this.http.post(environment.ISCRIZIONI_URL + 'competitions/getCompetitions', {});

    }

    getTatami()
     {
         return this.http.post(environment.BASEAPPURL + 'users/getTatami', {});
                 
    }

    getTatamistatus(){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/getUserCategories', {});
    }

    saveTatamiAssegnee(request){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/saveUserCategories', request);
    }

    getAthleteList(request){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getAthletesByCategory', request);
    }

    getTotaleIscrizioni(){
        return this.http.post(environment.BASEAPPURL + 'competitions/getTotaleIscrizioni', {});
    }

    splitCategory(request){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/splitCategory', request);
    }

    getFinalRanking(request){
        return this.http.post(environment.BASEAPPURL + 'results/getRankingData', request);
    }
} 