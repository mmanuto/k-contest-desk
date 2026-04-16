import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../environments/environment';


@Injectable()
export class AthletesService {
    public url = "api/users";
    constructor(public http:HttpClient) { }

//====================================================================================================
// Recupero dati
//====================================================================================================

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

    getAllCategories(){
        return this.http.post(environment.BASEAPPURL + "categorycodes/getAllCategories", {});
    }

    checkCategoryStatus(request){
        return this.http.post(environment.BASEAPPURL + 'tatamiAssignments/checkCategoryStatus', request);
    }

//====================================================================================================
// Salvataggio dati locale
//====================================================================================================

    saveAtleta(request){
        return this.http.post(environment.BASEAPPURL + "athletes/saveAtleta", request);
    }

    addInscription(request){
        return this.http.post(environment.BASEAPPURL + "athleteInscriptions/addInscription", request);
    }

    deteleAtletaInscription(request){
        console.log(request);
        return this.http.post(environment.BASEAPPURL + "athleteInscriptions/deleteInscription", request);
    }

    updateCategory(request){
        return this.http.post(environment.BASEAPPURL + "athleteInscriptions/updateCategory", request);
    }

//====================================================================================================
// Salvataggio dati WEB
//====================================================================================================

    saveAtletaWEB(request){
        return this.http.post(environment.ISCRIZIONI_URL + "athletes/saveAtleta", request);
    }

    addInscriptionWEB(request){
        return this.http.post(environment.ISCRIZIONI_URL + "athleteInscriptions/addInscription", request);
    }

    deteleAtletaInscriptionWEB(request){
        console.log(request);
        return this.http.post(environment.ISCRIZIONI_URL + "athleteInscriptions/deleteInscription", request);
    }

    updateCategoryWEB(request){
        return this.http.post(environment.ISCRIZIONI_URL + "athleteInscriptions/updateCategory", request);
    }

} 