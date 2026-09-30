import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../environments/environment';

@Injectable()
export class TatamiService
{
    
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }

    getTatamiStatus(request){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/getUserCategories', request);
    }

    getCategoryState(request){
        return this.http.post(environment.BASEAPPURL + 'results/getCategoryState', request);
    }

    getAthleteList(request){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getAthletesByCategory', request);
    }

    getKataList(){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/getKataList', {});
    }

    saveJudgeScore(request){
        //return this.http.post(environment.BASEAPPURL + 'scores/saveScoresAndGetTotals', request);
        return this.http.post(environment.BASEAPPURL + 'results/saveJudgeScore', request);
    }

    saveTimedScore(request){
        return this.http.post(environment.BASEAPPURL + 'results/saveTimedScore', request);
    }

    saveMatchResult(request) {
        return this.http.post(environment.BASEAPPURL + 'results/saveMatchResult', request);
    }

    deleteInscription(request){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/deleteInscription', request);
    }

    finalizeScoreOnlyCategory(request){
        return this.http.post(environment.BASEAPPURL + 'results/finalizeScoreOnlyCategory', request);
    }

    finalizeTimedCategory(request){
        return this.http.post(environment.BASEAPPURL + 'results/finalizeTimedCategory', request);
    }

    getKumiteFinalRanking(request){
        return this.http.post(environment.BASEAPPURL + 'results/getKumiteFinalRanking', request);
    }

    generateBrackets(request){
        return this.http.post(environment.BASEAPPURL + 'results/generateBrackets', request);
    }

    generateRepechage(request){
        return this.http.post(environment.BASEAPPURL + 'results/generateRepechage', request);
    }

    updateCategoryStatus(request){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/updateCategoryStatus', request);
    }
/*
    updateCategoryStatusWeb(request){
        return this.http.post(environment.ISCRIZIONI_URL + 'users/updateCategoryStatus', request);
    }*/

    getAthleteHistory(request){
        return this.http.post(environment.BASEAPPURL + 'results/getAthleteHistory', request);
    }

    getPreviousMatch(request){
        return this.http.post(environment.BASEAPPURL + 'scores/getPreviousMatch', request);
    }

    matchAthletes(request){
        return this.http.post(environment.BASEAPPURL + 'scores/matchAthletes', request);
    }

    saveAthleteScores(request){
        return this.http.post(environment.BASEAPPURL + 'scores/saveAthleteScores', request);
    }

}
