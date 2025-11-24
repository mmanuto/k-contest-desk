import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../environments/environment';

@Injectable()
export class LoginService
{
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }


    login(request)
    {
        return this.http.post(environment.BASEAPPURL + 'users/login', {'username': request.username, 'password': request.password});
                
   }

}