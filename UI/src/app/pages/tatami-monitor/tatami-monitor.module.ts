import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DirectivesModule } from '../../theme/directives/directives.module';
import { TatamiMonitorComponent } from './tatami-monitor.component';
import { TatamiMonitorService } from './tatami-monitor.service';

export const routes: Routes = [
  { path: '', component: TatamiMonitorComponent, pathMatch: 'full' }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    DirectivesModule,
    RouterModule.forChild(routes)
  ],
  declarations: [
    TatamiMonitorComponent
  ],
  providers: [
      TatamiMonitorService
    ]
})
export class TatamiMonitorModule { }
