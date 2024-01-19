import { Component, ViewEncapsulation } from '@angular/core';
import { AthletesService } from './athletes.service';
import { NgbModal, NgbModalRef } from '@ng-bootstrap/ng-bootstrap';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

@Component({
  selector: 'app-smart',
  templateUrl: './athletes.component.html',
  styleUrls: ['./athletes.component.scss'],
  encapsulation: ViewEncapsulation.None
})

export class AthletesComponent {
  public data = [];
  public athleteList = [];
  public athletes: any[];
  public clubList = [];
  public modalRef: NgbModalRef;
  public steps: any[];
  public athleteForm: FormGroup;
  public weightForm: FormGroup;
  public atleta: any = {};
  public showConfirm: boolean;
  public confirmed: boolean;
  public prove: any[];
  public show = false;
  public categoria = "";
  public erroreCategoria = false;
  public errorePeso = false;

  public categoryForm: FormGroup;
  public categoryList: any[];
  public selectedInscription;

  public datiGara: any;

  config = {
    search:true,
    height: '400px', //height of the list so that if there are more no of items it can show a scroll defaults to auto. With auto height scroll will never appear
    placeholder:'Recupera atleti inseriti in gare precedenti', // text to be displayed when no item is selected defaults to Select,
    noResultsFound: 'Nessun risultato', // text to be displayed when no items are found while searching
    searchPlaceholder:'Cerca', // label thats displayed in search input,
    displayKey: 'nominativo', 
    searchOnKey: 'nominativo', // key on which search should be performed this will be selective search. if undefined this will be extensive search on all keys
    inputDirection: 'lrtl', // the direction of the search input can be rtl or ltr(default)
    enableSelectAll: false, // enable select all option to select all available items, default is false
  }

  public settings = {
    selectMode: 'single',  //single|multi
    hideHeader: false,
    hideSubHeader: false,
    actions: {
      columnTitle: 'Actions',
      add: false,
      edit: false,
      delete: true,
      custom: [{
        name: 'edit-category',
        title: '<i class="fa fa-pencil mr-3 text-primary"></i>'
      }],
      position: 'right' // left|right
    },
    edit: {
      editButtonContent: '<i class="fa fa-pencil mr-3 text-primary"></i>',
      saveButtonContent: '<i class="fa fa-check mr-3 text-success"></i>',
      cancelButtonContent: '<i class="fa fa-times text-danger"></i>'
    },
    delete: {
      deleteButtonContent: '<i class="fa fa-trash-o text-danger"></i>',
      confirmDelete: true
    },
    noDataMessage: 'No data found',
    rowClassFunction: (row) =>{
      console.log(row);
      if(row.data.deleted){
        return 'red-background';
      }else if(row.data.modified){
        return 'orange-background'
      }else{
        return 'none-background';
      }
    },
    columns: {     
      id: {
        title: 'ID',
        editable: false,
        width: '60px',
        type: 'html',
        valuePrepareFunction: (value) => { return '<div class="text-center">' + value + '</div>'; }       
      },
      firstName: {
        title: 'Nome',
        type: 'string',
        filter: true
      },
      lastName: {
        title: 'Cognome',
        type: 'string'
      },
      club: {
        title: 'Società',
        type: 'string'
      },
      birthdate: {
        title: 'Data di Nascita',
        type: 'string'
      },
      belt: {
        title: 'Cintura',
        type: 'string'
      },
      codes: {
        title: 'Prove',
        type: 'string'
      }
    },
    pager: {
      display: true,
      perPage: 25
    }
  };

  constructor(public athletesService:AthletesService,
    public fb: FormBuilder,
    public modalService: NgbModal) { 
    this.steps = [
      { name: 'dati atleta', icon: 'fa-lock', active: true, valid: false, hasError: false },
      { name: 'selezione prove', icon: 'fa-user', active: false, valid: false, hasError: false }
    ]

    this.prove = [];

    this.athleteForm = this.fb.group({
      'id': [''],
      'name': ['', Validators.required],
      'surname': ['', Validators.required],
      'birthdate_day': ['', Validators.required],
      'birthdate_month': ['', Validators.required],
      'birthdate_year': ['', Validators.required],
      'club': ['', Validators.required],
      'sesso': ['', Validators.required],
      'cintura': ['', Validators.required]
    });

    this.weightForm = this.fb.group({
      'peso': ['', Validators.required]
    });


    this.categoryForm = this.fb.group({
      'inscription_id': [''],
      'categorycode_id': [''],
      'accorpamento':['']
    });
  }

  ngOnInit(){

    this.athletesService.getAthleteList().subscribe((response: any) => {
      this.athleteList = response.result.data;

      this.buildTable();
    });

    this.athletesService.getTotalAthleteList().subscribe((response: any) => {
      this.athletes = response.result.data;
      this.athletes.forEach(element => {
        element.nominativo = element.cognome + ' ' + element.nome;
      });
    })

    this.athletesService.getClubs().subscribe((response: any) => {
      this.clubList = response.result.data;
    });

    this.athletesService.getAllCategories().subscribe((response: any) => {
      this.categoryList = response.result.data;
    });

  }

  public buildTable(){

    this.data = [];
    console.log(this.athleteList);
    this.athleteList.forEach(element => {
      let dataItem = {
          id: element.id,
          firstName: element.athlete.nome,
          lastName: element.athlete.cognome,
          club: element.athlete.club.nome_societa,
          birthdate: element.athlete.data_nascita,
          belt: element.athlete.grado,
          codes: element.categorycode.codice,
          categorycode_id: element.categorycode_id,
          modified: element.modificato == 1 ? true:false,
          deleted: element.deleted == 1 ? true:false
      }

      this.data.push(dataItem);
    });
  }

   public onDeleteConfirm(event): void {
    if (window.confirm('Are you sure you want to delete?')) {
      console.log(event);
      this.athletesService.deteleAtletaInscription({ 'id': event.data.id, 'code': event.data.codes, 'categorycode_id':event.data.categorycode_id}).subscribe((response: any) => {
        console.log(response);
        if (response.result.success) {
          location.reload();
        }
  
      });
      event.confirm.resolve();
    } else {
      event.confirm.reject();
    }
  }

  public onCustom(event, editCategory) {
    this.selectedInscription = event.data;

    this.categoryForm.value.inscription_id = this.selectedInscription.id;
    this.modalRef = this.modalService.open(editCategory, { container: '.app', backdrop: 'static', size: 'lg',  });
  }

  public updateCategory(){

    let request = {
      id: this.selectedInscription.id,
      categorycode_id: this.categoryForm.value.categorycode_id,
      accorpamento: this.categoryForm.value.accorpamento
    }

    this.athletesService.updateCategory(request).subscribe((response: any) => {
      this.closeModal();
    });
  }

  public next() {
    let athleteForm = this.athleteForm;


    if (this.steps[this.steps.length - 1].active)
      return false;
    if (this.steps[0].active) {
      if (athleteForm.valid && this.categoria != "") {
        this.erroreCategoria = false;
        let request = { anno: this.athleteForm.value.birthdate_year, grado: this.athleteForm.value.cintura, category_code: 3 };
        this.athletesService.getElencoProve(request).subscribe((response: any) => {

          if (response.result.success) {
            this.prove = [];
            for (let prova of response.result.data) {
              this.prove.push({ label: prova.specialita, checked: false });
            }
          }
          console.log(response);
          this.steps[0].active = false;
          this.steps[0].valid = true;
          this.steps[1].active = true;
          return true;


        });

      }
      else {
        this.steps[0].hasError = true;
        if (this.categoria == '') {
          this.erroreCategoria = true;
        }
      }
    }


    this.atleta.nome = this.athleteForm.value.name;
    this.atleta.cognome = this.athleteForm.value.surname;
    this.atleta.data_nascita = this.athleteForm.value.birthdate_day + '/' + this.weightForm.value.birthdate_month + '/' + this.weightForm.value.birthdate_year;
    this.atleta.sesso = this.athleteForm.value.sesso;
    this.atleta.grado = this.athleteForm.value.cintura;
    this.atleta.categoria = this.categoria;
  }
  /*pulsante per tornare indietro nel widget*/
  public prev() {
    if (this.steps[0].active)
      return false;
    this.steps.some(function (step, index, steps) {
      if (index != 0) {
        if (step.active) {
          step.active = false;
          steps[index - 1].active = true;
          return true;
        }
      }
    });
  }



  public showPeso() {
    let kumite = true;
    let kumiteRagazzi = true;

    for (let item of this.prove) {
      if ((item.label == "Kumite" || item.label == "Kumite U12") && item.checked) {
        this.show = true;
        break;
      } else if (item.label == "Kumite" && !item.checked) {
        kumite = false;
      } else if (item.label == "Kumite U12" && !item.checked) {
        kumiteRagazzi = false;
      }
    }

    if (!kumite && !kumiteRagazzi) {
      this.show = false;
    }
  }

  selectionChanged(event){
    console.log(event.value);
    this.atleta = event.value;

    let birthdate = this.atleta.data_nascita.split('/');

    this.athleteForm = this.fb.group({
      'id': [this.atleta.id],
      'name': [this.atleta.nome, Validators.required],
      'surname': [this.atleta.cognome, Validators.required],
      'birthdate_day': [birthdate[0], Validators.required],
      'birthdate_month': [birthdate[1], Validators.required],
      'birthdate_year': [birthdate[2], Validators.required],
      'sesso': [this.atleta.sesso, Validators.required],
      'cintura': [this.atleta.grado, Validators.required]
    });

    this.checkCategory();
  }

  checkCategory() {

    let params = {
      'anno': this.athleteForm.value.birthdate_year,
      'categoryCode': 3
    }

    this.athletesService.getCategoria(params).subscribe((response: any) => {
      if (response.result.success) {
        this.categoria = response.result.data[0].categoria;
      } else {
        this.categoria = "";
      }
    });
  }

  public openModal(modalContent) {
    this.erroreCategoria = false;
    this.steps = [
      { name: 'dati atleta', icon: 'fa-lock', active: true, valid: false, hasError: false },
      { name: 'selezione prove', icon: 'fa-user', active: false, valid: false, hasError: false }
    ];
    
      this.atleta = {};
      this.categoria = "";
      this.prove = [];
      this.athleteForm.reset();
      this.weightForm.reset();
    this.modalRef = this.modalService.open(modalContent, { container: '.app', backdrop: 'static', size: 'lg' });

    this.modalRef.result.then((result) => {
      this.athleteForm.reset();
      this.weightForm.reset();
    }, (reason) => {
      this.athleteForm.reset();
      this.weightForm.reset();
    });
  }

  public closeModal() {
    this.modalRef.close();
  }

  public confirm() {

    //Controllo sia stata selezionata almeno una prova
    let provaScelta = false;
    for (let item of this.prove) {
      if (item.checked) {
        provaScelta = true;
        break;
      }
    }

    if (provaScelta) {
      var objKumite = this.prove.find(function (obj) { return obj.label == "Kumite"; });
      var objSemi = this.prove.find(function (obj) { return obj.label == "Semi Contact"; });
      var objLight = this.prove.find(function (obj) { return obj.label == "Light Contact"; });

      var kumiteOk = false;
      var semiOk = false;
      var lightOk = false;

      if (objKumite == undefined || (objKumite != undefined && objKumite.checked && this.weightForm.value.peso != "" && this.weightForm.value.peso > 10) || (objKumite != undefined && !objKumite.checked)) {
        kumiteOk = true;
      }
      if (objSemi == undefined || (objSemi != undefined && objSemi.checked && this.weightForm.value.peso != "" && this.weightForm.value.peso > 10) || (objSemi != undefined && !objSemi.checked)) {
        semiOk = true;
      }
      if (objLight == undefined || (objLight != undefined && objLight.checked && this.weightForm.value.peso != "" && this.weightForm.value.peso > 10) || (objLight != undefined && !objLight.checked)) {
        lightOk = true;
      }

      if (kumiteOk && semiOk && lightOk) {

        this.errorePeso = false;
        //TODO: Controllare recupero corretto dati società e gara
        this.atleta.club_id = '';
        this.atleta.nome = this.athleteForm.value.name;
        this.atleta.cognome = this.athleteForm.value.surname;
        this.atleta.data_nascita = this.athleteForm.value.birthdate_day + '/' + this.athleteForm.value.birthdate_month + '/' + this.athleteForm.value.birthdate_year;
        this.atleta.cod_fiscale = this.athleteForm.value.cod_fiscale;
        this.atleta.federation_id = this.athleteForm.value.federation;
        this.atleta.n_tessera = this.athleteForm.value.n_tessera;
        this.atleta.sesso = this.athleteForm.value.sesso;
        this.atleta.grado = this.athleteForm.value.cintura;
        //this.atleta.categoria = this.categoria;
        this.atleta.peso = this.weightForm.value.peso;

        let elencoProve = {
          'categoryCode': 3,
          'competition_id': this.datiGara.id,
          'specialita': []
        }
        for (let prova of this.prove) {
          if (prova.checked) {
            elencoProve.specialita.push(prova.label);
          }
        }
          this.athletesService.saveAtleta({ 'athlete': this.atleta, 'prove': elencoProve }).subscribe((response: any) => {
            if (response.result.success) {
              this.steps = [
                { name: 'dati atleta', icon: 'fa-lock', active: true, valid: false, hasError: false },
                { name: 'selezione prove', icon: 'fa-user', active: false, valid: false, hasError: false }
              ];
              this.prove = [];
              this.categoria = '';
              //this.annoNascita = '';
              this.closeModal();
              //this.toastrService.success('Atleta salvato');
              location.reload();
            }
          });



      }
      else {
        this.errorePeso = true;
        this.confirmed = false;
      }

    }
    else {
      this.confirmed = false;
    }


  }

}
