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

    saveTatamiAssegnee(request: { user_id: any; categorycode_id: any; status: string; }){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/saveUserCategories', request);
    }

    updateTatamiWeb(request: { tatami: any; competition_id: string; categorycode_id: any; status: string; }){
        return this.http.post(environment.ISCRIZIONI_URL + 'users/addTatamiAssignments', request);
    }

    getAthleteList(request: { categorycode_id: any; readonly: boolean; }){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getAthletesByCategory', request);
    }

    getTotaleIscrizioni(){
        return this.http.post(environment.BASEAPPURL + 'competitions/getTotaleIscrizioni', {});
    }

    getTotaleProve(){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getTotaleProve', {});
    }

    splitCategory(request: any){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/splitCategory', request);
    }

    getFinalRanking(request: { categorycode_id: any; }){
        return this.http.post(environment.BASEAPPURL + 'results/getRankingData', request);
    }

    getClassificaSocieta(): Observable<any[]> {
        return this.http.get<any[]>(environment.BASEAPPURL + '/clubs/getClassificaSocieta');
}
} 