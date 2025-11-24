import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../environments/environment';


@Injectable()
export class AthletesService {
    public url = "api/users";
    constructor(public http:HttpClient) { }


    getAthleteList(){
        return this.http.post(environment.BASEAPPURL + 'athleteInscriptions/getAthleteList', {});
    }

    getClubs(){
        return this.http.post(environment.BASEAPPURL + 'clubs/getClubs', {});
    }



    getCategoria(request){
        return this.http.post(environment.BASEAPPURL + "athletes/getCategoria", request);
        
    }

    getElencoProve(request){
        return this.http.post(environment.BASEAPPURL + "athletes/getElencoProve", request);
    }

    getTotalAthleteList(){
        return this.http.post(environment.BASEAPPURL + "athletes/getAthleteList", {});
    }


    getListaIscritti(request){
        return this.http.post(environment.BASEAPPURL + "athletes/getIscritti", request);
    }

    saveAtleta(request){
        return this.http.post(environment.BASEAPPURL + "athletes/saveAtleta", request);
    }

    deteleAtletaInscription(request){
        console.log(request);
        return this.http.post(environment.BASEAPPURL + "athleteInscriptions/deleteInscription", request);
    }

    getAllCategories(){
        return this.http.post(environment.BASEAPPURL + "categorycodes/getAllCategories", {});
    }

    updateCategory(request){
        return this.http.post(environment.BASEAPPURL + "athleteInscriptions/updateCategory", request);
    }

    checkCategoryStatus(request){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/checkCategoryStatus', request);
    }
} 