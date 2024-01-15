import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { NgxDatatableModule } from '@swimlane/ngx-datatable';
import { Ng2SmartTableModule, LocalDataSource } from 'ng2-smart-table';
import { DirectivesModule } from '../../../theme/directives/directives.module';
import { NgxComponent } from './ngx/ngx.component';
import { SmartComponent } from './smart/smart.component'

export const routes: Routes = [
  { path: '', redirectTo: 'smart', pathMatch: 'full'},
  { path: 'ngx', component: NgxComponent, data: { breadcrumb: 'NGX DataTable' } }, 
  { path: 'smart', component: SmartComponent, data: { breadcrumb: 'NGX DataTable' } }  
];

@NgModule({
  imports: [
    CommonModule,
    RouterModule,
    NgxDatatableModule,
    Ng2SmartTableModule,
    DirectivesModule,
    RouterModule.forChild(routes)
  ],
  declarations: [
    NgxComponent,
    SmartComponent   
  ]
})
export class DynamicTablesModule { }
