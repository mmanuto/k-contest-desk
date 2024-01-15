import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { DirectivesModule } from '../../theme/directives/directives.module';
import { AdminPanleComponent } from './admin-panel.component';
import { AdminPanelService } from './admin-panel.service';
import { CategoriesService } from '../categories/categories.service';


export const routes: Routes = [
  { path: '', component: AdminPanleComponent, pathMatch: 'full' }
];

@NgModule({
  imports: [
    CommonModule,
    DirectivesModule,
    RouterModule.forChild(routes)
  ],
  declarations: [
    AdminPanleComponent
  ],
  providers: [
    AdminPanelService,
    CategoriesService
  ]
})
export class AdminPanelModule { }
