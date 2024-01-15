import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'CategorySearchPipe', pure: false })
export class CategorySearchPipe implements PipeTransform {
  transform(value, args?): Array<any> {
    let searchText = new RegExp(args, 'ig');
    if (value) {
      return value.filter(athlete => {
        
          return athlete.athlete.nome.search(searchText) !== -1 ||
          athlete.athlete.cognome.search(searchText) !== -1;
        
      });
    }
  }
}