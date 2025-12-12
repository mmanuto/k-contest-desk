import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { NgxDatatableModule } from '@swimlane/ngx-datatable';
import { Ng2SmartTableModule, LocalDataSource } from 'ng2-smart-table';
import { DirectivesModule } from '../../theme/directives/directives.module';
import { TatamiComponent, FormatTimePipe } from './tatami.component';
import { TatamiService } from './tatami.service';
import { FormsModule, ReactiveFormsModule }   from '@angular/forms';
import { NgxCurrencyModule } from "ngx-currency";
import { NgSelectModule } from '@ng-select/ng-select';
import { ModalModule, BsModalService } from 'ngx-bootstrap/modal';
import { PipesModule } from '../../theme/pipes/pipes.module';
import { StageType } from 'brackets-model';

declare global {
  interface Window {
    bracketsViewer?: any | undefined;
  }

  interface Dataset {
    title: string;
    type: StageType;
    roster: { position: string, id: number, name: string, club:string }[];
  }

  interface Tabset {
    number: number;
    positions:  any;
  }
}

export const routes: Routes = [
  { path: '', component: TatamiComponent, pathMatch: 'full' }
];

@NgModule({
  imports: [
    CommonModule,
    RouterModule,
    NgxDatatableModule,
    Ng2SmartTableModule,
    DirectivesModule,
    RouterModule.forChild(routes),
    ModalModule.forRoot(),
    FormsModule,
    ReactiveFormsModule,
    NgxCurrencyModule,
    NgSelectModule,
    PipesModule
  ],
  declarations: [
    TatamiComponent, FormatTimePipe  
  ],
  providers: [TatamiService, BsModalService]
})
export class TatamiModule { }
