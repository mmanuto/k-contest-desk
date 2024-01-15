import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { DisplayInfoComponent, FormatTimePipe } from './displayinfo.component';
import { DisplayInfoService } from './displayinfo.service';
import { ModalModule } from 'ngx-bootstrap/modal';
import { PipesModule } from '../../theme/pipes/pipes.module';
//import { DirectivesModule } from '../../../theme/directives/directives.module';

import { CollapseModule } from 'ngx-bootstrap/collapse';
import { TabsModule } from 'ngx-bootstrap/tabs';


export const routes: Routes = [
  { path: '', component: DisplayInfoComponent, pathMatch: 'full' }
];

@NgModule({
  imports: [
    CommonModule,
    HttpClientModule,
    RouterModule,
    //DirectivesModule,
    RouterModule.forChild(routes),
    FormsModule,
    ReactiveFormsModule,
    ModalModule.forRoot(),
    TabsModule,
    CollapseModule.forRoot(),
    FormsModule,
    PipesModule
  ],
  declarations: [
    DisplayInfoComponent, FormatTimePipe
  ],
  providers: [
    DisplayInfoService,
]
})

export class DisplayInfoModule { 

}
