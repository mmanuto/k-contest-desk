import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ActivatedRouteSnapshot, Resolve, RouterStateSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import * as Constants from '../../constants';

@Injectable()
export class DisplayInfoService
{
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }

}
