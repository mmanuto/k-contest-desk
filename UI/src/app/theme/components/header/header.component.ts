import { Component, OnInit, ViewEncapsulation, HostListener } from '@angular/core';
import { trigger, state, style, transition, animate } from '@angular/animations';
import { AppSettings } from '../../../app.settings';
import { Settings } from '../../../app.settings.model';
import { LocalStorageService } from 'angular-2-local-storage';

import { MenuService } from '../menu/menu.service';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
  encapsulation: ViewEncapsulation.None,
  providers: [MenuService],
  animations: [
    trigger('showInfo', [
      state('1', style({ transform: 'rotate(180deg)' })),
      state('0', style({ transform: 'rotate(0deg)' })),
      transition('1 => 0', animate('400ms')),
      transition('0 => 1', animate('400ms'))
    ])
  ]
})
export class HeaderComponent implements OnInit {
  public showHorizontalMenu: boolean = true;
  public showInfoContent: boolean = false;
  public settings: Settings;
  public menuItems: Array<any>;
  public currentUser;


  constructor(public appSettings: AppSettings, public menuService: MenuService, protected localStorageService: LocalStorageService) {
    this.settings = this.appSettings.settings;
    this.menuItems = this.menuService.getHorizontalMenuItems();
    this.currentUser = this.localStorageService.get('currentUser');
  }

  ngOnInit() {
    this.currentUser = this.localStorageService.get('currentUser');
  }

  /*logout() {
    this.storageService.remove('authToken');

    this.storageService.remove('currentUser');
    this.storageService.remove('userType');

    this.router.navigate(['register']);
  }*/


  public closeSubMenus() {
    let menu = document.querySelector("#menu0");
    if (menu) {
      for (let i = 0; i < menu.children.length; i++) {
        let child = menu.children[i].children[1];
        if (child) {
          if (child.classList.contains('show')) {
            child.classList.remove('show');
            menu.children[i].children[0].classList.add('collapsed');
          }
        }
      }
    }
  }

}
