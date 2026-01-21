import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TatamiStatus } from './tatami.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})

export class TatamiMonitorService {

  constructor(public http:HttpClient) { }

  getTatamiStatus(): Observable<TatamiStatus[]> {
    return this.http.get<TatamiStatus[]>(environment.BASEAPPURL + 'tatamiAssignments/getTatamiStatus');
  }
}
