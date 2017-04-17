import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'helpContentCategoryFilter'
})
export class HelpContentCategoryFilterPipe implements PipeTransform {

  transform(value: any, args?: any): any {
    return value.filter(helpItem => {
      return (args !== undefined) ? (helpItem.category === args) : helpItem;
    });
  }

}
