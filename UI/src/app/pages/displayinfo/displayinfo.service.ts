import { Injectable, Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable()
export class DisplayInfoService
{
    //public url = "api/users";
    constructor(private http: HttpClient)
    {
    }

}
