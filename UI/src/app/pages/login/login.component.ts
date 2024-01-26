import { Component, ViewEncapsulation } from '@angular/core';
import { Router } from '@angular/router';
import { UntypedFormGroup, FormControl, AbstractControl, UntypedFormBuilder, Validators} from '@angular/forms'; 
import { EmailValidators } from 'ngx-validators'
import { LoginService } from './login.service';
import { LocalStorageService } from 'angular-2-local-storage';

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
                this.localStorageService.set('userType', 1);
              }else{
                this.router.navigate(['tatami']);
                this.localStorageService.set('userType', 0);
              }
  
                console.log(response.result.data);
            } 
  
        });
      }
  }

  ngAfterViewInit(){
      document.getElementById('preloader').classList.add('hide');                 
  }

}
