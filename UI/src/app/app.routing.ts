import { NgModule } from '@angular/core';
import { Routes, RouterModule, PreloadAllModules  } from '@angular/router'; 
import { PagesComponent } from './pages/pages.component';
import { SearchComponent } from './pages/search/search.component';
import { NotFoundComponent } from './pages/errors/not-found/not-found.component';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: 'login', loadChildren: () => import('./pages/login/login.module').then(m => m.LoginModule) },
  { path: 'register', loadChildren: () => import('./pages/register/register.module').then(m => m.RegisterModule) },
  { path: 'displayinfo', loadChildren: () => import('./pages/displayinfo/displayinfo.module').then(m => m.DisplayInfoModule)},
  { path: '', pathMatch: 'full', redirectTo: 'login'},
  {
    path: '', 
    component: PagesComponent,
    canActivate: [AuthGuard],
    canActivateChild: [AuthGuard],
    children:[
      { path: '', loadChildren: () => import('./pages/dashboard/dashboard.module').then(m => m.DashboardModule), data: { breadcrumb: 'Dashboard' }  },          
      { path: 'categories', loadChildren: () => import('./pages/categories/categories.module').then(m => m.CategoriesModule)},
      { path: 'tatami', loadChildren: () => import('./pages/tatami/tatami.module').then(m => m.TatamiModule)},
      { path: 'membership', loadChildren: () => import('./pages/membership/membership.module').then(m => m.MembershipModule), data: { breadcrumb: 'Membership' } },
      { path: 'adminpanel', loadChildren: () => import('./pages/admin-panel/admin-panel.module').then(m => m.AdminPanelModule )},
      { path: 'athlete-list', loadChildren: () => import('./pages/athletes/athletes.module').then(m => m.AthletesModule )},
      { path: 'ui', loadChildren: () => import('./pages/ui/ui.module').then(m => m.UiModule), data: { breadcrumb: 'UI' } },
      { path: 'form-elements', loadChildren: () => import('./pages/form-elements/form-elements.module').then(m => m.FormElementsModule), data: { breadcrumb: 'Form Elements' } },
      { path: 'tables', loadChildren: () => import('./pages/tables/tables.module').then(m => m.TablesModule), data: { breadcrumb: 'Tables' } },
      { path: 'tools', loadChildren: () => import('./pages/tools/tools.module').then(m => m.ToolsModule), data: { breadcrumb: 'Tools' } },
      { path: 'tatami-monitor', loadChildren: () => import('./pages/tatami-monitor/tatami-monitor.module').then(m => m.TatamiMonitorModule)},
      { path: 'mailbox', loadChildren: () => import('./pages/mailbox/mailbox.module').then(m => m.MailboxModule), data: { breadcrumb: 'Mailbox' } },
      { path: 'maps', loadChildren: () => import('./pages/maps/maps.module').then(m => m.MapsModule), data: { breadcrumb: 'Maps' } },
      { path: 'charts', loadChildren: () => import('./pages/charts/charts.module').then(m => m.ChartsModule), data: { breadcrumb: 'Charts' } },
      { path: 'dynamic-menu', loadChildren: () => import('./pages/dynamic-menu/dynamic-menu.module').then(m => m.DynamicMenuModule), data: { breadcrumb: 'Dynamic Menu' }  },  
      { path: 'profile', loadChildren: () => import('./pages/profile/profile.module').then(m => m.ProfileModule), data: { breadcrumb: 'Profile' }  },         
      { path: 'search', component: SearchComponent, data: { breadcrumb: 'Search' } }
    ]
  },
  { path: '**', redirectTo: 'login'}
]; 

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {
      preloadingStrategy: PreloadAllModules, // <- comment this line for activate lazy load
      relativeLinkResolution: 'legacy',
      // useHash: true
    })
  ],
  exports: [
    RouterModule
  ]
})
export class AppRoutingModule { }