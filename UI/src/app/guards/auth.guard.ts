import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  CanActivate,
  CanActivateChild,
  Router,
  RouterStateSnapshot,
  UrlTree
} from '@angular/router';
import { LocalStorageService } from 'angular-2-local-storage';

@Injectable({
    providedIn: 'root'
})
export class AuthGuard implements CanActivate, CanActivateChild {

    constructor(private router: Router, private localStorageService: LocalStorageService) {}


    canActivate(
        route: ActivatedRouteSnapshot,
        state: RouterStateSnapshot
    ): boolean | UrlTree {
        return this.checkAuthentication(state.url);
    }

    canActivateChild(
        childRoute: ActivatedRouteSnapshot,
        state: RouterStateSnapshot
    ): boolean | UrlTree {
        return this.checkAuthentication(state.url);
    }


    private checkAuthentication(returnUrl: string): boolean | UrlTree {
        // Usa il nome esatto della chiave salvata dal login.
        const user = this.localStorageService.get('currentUser');
        console.log(user);

        if (user) {
            return true;
        }

        return this.router.createUrlTree(['/login'], {
        queryParams: { returnUrl }
        });
    }

}