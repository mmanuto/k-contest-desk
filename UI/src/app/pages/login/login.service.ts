import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import * as Constants from '../../constants';

@Injectable()
export class LoginService
{
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }


    login(request)
    {
        return this.http.post(Constants.BASEAPPURL + 'users/login', {'username': request.username, 'password': request.password});
                
   }

}