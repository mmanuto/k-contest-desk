import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import * as Constants from '../../constants';


@Injectable()
export class AthletesService {
    public url = "api/users";
    constructor(public http:HttpClient) { }


    getAthleteList(){
        return this.http.post(Constants.BASEAPPURL + 'athleteInscriptions/getAthleteList', {});
    }

    getClubs(){
        return this.http.post(Constants.BASEAPPURL + 'clubs/getClubs', {});
    }



    getCategoria(request){
        return this.http.post(Constants.BASEAPPURL + "athletes/getCategoria", request);
        
    }

    getElencoProve(request){
        return this.http.post(Constants.BASEAPPURL + "athletes/getElencoProve", request);
    }

    getTotalAthleteList(){
        return this.http.post(Constants.BASEAPPURL + "athletes/getAthleteList", {});
    }


    getListaIscritti(request){
        return this.http.post(Constants.BASEAPPURL + "athletes/getIscritti", request);
    }

    saveAtleta(request){
        return this.http.post(Constants.BASEAPPURL + "athletes/saveAtleta", request);
    }

    deteleAtletaInscription(request){
        console.log(request);
        return this.http.post(Constants.BASEAPPURL + "athleteInscriptions/deleteInscription", request);
    }

    getAllCategories(){
        return this.http.post(Constants.BASEAPPURL + "categorycodes/getAllCategories", {});
    }

    updateCategory(request){
        return this.http.post(Constants.BASEAPPURL + "athleteInscriptions/updateCategory", request);
    }
} 