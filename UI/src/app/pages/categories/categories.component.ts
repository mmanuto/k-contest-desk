import { Component, OnInit, ViewChild, ViewEncapsulation} from '@angular/core';
import { UntypedFormGroup, FormControl, UntypedFormBuilder, Validators, FormGroup} from '@angular/forms';
import { IMultiSelectOption, IMultiSelectSettings, IMultiSelectTexts } from 'angular-2-dropdown-multiselect';
import { NgbModal, NgbModalRef } from '@ng-bootstrap/ng-bootstrap';
import { ToastrService } from 'ngx-toastr';
import { CategoriesService } from './categories.service';
import { MenuService } from '../../theme/components/menu/menu.service';
import { TranslateService } from '@ngx-translate/core';
import { ModalDirective } from 'ngx-bootstrap/modal';
import {interval,Subscription} from 'rxjs';
 
@Component({
  selector: 'app-membership',
  templateUrl: './categories.component.html',
  styleUrls: ['./categories.component.scss'],
  encapsulation: ViewEncapsulation.None,
  providers: [ CategoriesService, MenuService ]
})
export class CategoriesComponent implements OnInit {

  @ViewChild('primaryModal') public primaryModal: ModalDirective;
  @ViewChild('listModal') public listModal: ModalDirective;

  public searchText: string;
  public p:any;
  public type:string = 'list';
  

  categories: any[];
  athleteList: any[];
  tatamiList: any[];
  tatamiStatus: any[];
  public athletestNumber = null;
  selectedCategory: any = null;
  mySubscription: Subscription;

  alertsDismiss: any = [];

  public tatamiForm: FormGroup;
  
  constructor(public fb:UntypedFormBuilder, 
              public toastrService: ToastrService,
              public translateService: TranslateService,
              public categoriesService:CategoriesService,
              public menuService:MenuService, 
              public modalService: NgbModal) {

                this.mySubscription= interval(50000).subscribe((x =>{
                  this.getCategories();
              })); 

    this.tatamiForm = this.fb.group({
      'user_id': [''],
      'categorycode_id': ['']
    });
  }

  ngOnInit() {

    this.categoriesService.getTotaleIscrizioni().subscribe((response: any) => {
      this.athletestNumber = response.result.data;
    });

    //this.getUsers(); 
    this.getCategories();

    this.categoriesService.getTatami().subscribe((response: any) => {
      this.tatamiList = response.result.data;
    });

    this.getTatamiStatus();

    //this.categoriesService.getCompetitions().subscribe((response: any) => {
      //console.log(response);
    //});

  }

  getCategories(){
    this.categoriesService.getCategories().subscribe((response: any) => {
      this.categories = response.result.data;
      console.log(this.categories);
    });
  }

  public toggle(type){
    this.type = type;
  }

  getTatamiStatus(){
    this.categoriesService.getTatamistatus().subscribe((response: any) => {
      this.tatamiStatus = response.result.data;
    });
  }

  showTatamiStatus(){
    this.getTatamiStatus();
    console.log('CATEGORIA', this.tatamiForm.value.categorycode_id);
    console.log('TATAMI', this.tatamiForm.value.user_id);
  }

  //--------------------------------------------- Assegna a tatami --------------------------------------------------------------------
  openTatamiModal(category_id){
    console.log(category_id);
    this.tatamiForm = this.fb.group({
      'user_id': [''],
      'categorycode_id': [category_id]
    });
    this.primaryModal.show();
  }

  assignTatami(){
    let request = {
      user_id: this.tatamiForm.value.user_id,
      categorycode_id: this.tatamiForm.value.categorycode_id,
      status: 1
    }

    this.categoriesService.saveTatamiAssegnee(request).subscribe((response: any) => {

      if(response.result.success){
        this.alertsDismiss.push({
          type: 'success',
          msg: `Categoria assegnata al tatami ${new Date().toLocaleTimeString()})`,
          timeout: 5000
        });
        this.primaryModal.hide();
        console.log('ANDATA');
        this.getCategories();
      }else{
        this.alertsDismiss.push({
          type: 'error',
          msg: `Operazione non andata a buon fine`,
          timeout: 5000
        });
        console.log('ERROR');
      }
      
    });
  }

  //--------------------------------------------------------------------- LISTA ATLETI --------------------------------------------------------------
  openCategory(category){
    this.categoriesService.getAthleteList({categorycode_id: category.id}).subscribe((response: any) => {
      this.athleteList = response.result.data;
      this.selectedCategory = category;
    });

    this.listModal.show();
  }

  // ------------------------------------------------------------------------ CLASSIFICA -------------------------------------------------------------

  public openClassification(category_id){

  }

  // ------------------------------------------------------------------------ GENERA TABELLONE -------------------------------------------------------
  public generatePdf(category_id){

  }

  // ------------------------------------------------------------------ GESTIONE CARDS ---------------------------------------------------------------------
  public openMenuAssign(event){
    let parent = event.target.parentNode;
    while (parent){
      parent = parent.parentNode;
      if(parent.classList.contains('content')){
        parent.classList.add('flipped');
        parent.parentNode.parentNode.classList.add('z-index-1');
        break;
      }
    }
  }

  public closeMenuAssign(event){
    let parent = event.target.parentNode;
    while (parent){
      parent = parent.parentNode;
      if(parent.classList.contains('content')){
        parent.classList.remove('flipped');
        parent.parentNode.parentNode.classList.remove('z-index-1');
        break;
      }
    }
  }

}
