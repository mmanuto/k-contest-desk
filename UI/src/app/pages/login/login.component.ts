import { Component, ViewEncapsulation } from '@angular/core';
import { Router } from '@angular/router';
import { UntypedFormGroup, FormControl, AbstractControl, UntypedFormBuilder, Validators} from '@angular/forms'; 
import { ToastrService } from 'ngx-toastr';
import { LoginService } from './login.service';
import { LocalStorageService } from 'angular-2-local-storage';
import {userRole} from '../../constants';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
  encapsulation: ViewEncapsulation.None
})
export class LoginComponent {
  public router: Router;
  public form:UntypedFormGroup;
  public username:AbstractControl;
  public password:AbstractControl;

  constructor(router:Router,
    public toastrService: ToastrService, 
    fb:UntypedFormBuilder,
    public loginService: LoginService,
    protected localStorageService: LocalStorageService) {
      this.router = router;
      this.form = fb.group({
          'username': ['', Validators.required],
          'password': ['', Validators.required]
      });

      this.username = this.form.controls['username'];
      this.password = this.form.controls['password'];
  }

  public onSubmit(values:Object):void {
      if (this.form.valid) {
        this.loginService.login(this.form.value).subscribe((response: any) => {
          
            if (response.result.success) {
  
                //Salvo in sessione l'utente e il token
                this.localStorageService.set('authToken', response.result.token);
                this.localStorageService.set('currentUser', response.result.data);
              if(response.result.data.username == 'Admin'){
                this.router.navigate(['adminpanel']);
                this.localStorageService.set('userType', userRole.ADMIN);
              }else{
                this.router.navigate(['tatami']);
                this.localStorageService.set('userType', userRole.TATAMI_USER);
              }
  
                console.log(response.result.data);
            } else{
              this.toastrService.error('Credenziali errate');
            }
  
        });
      }
  }

  ngAfterViewInit(){
      document.getElementById('preloader').classList.add('hide');                 
  }

}
