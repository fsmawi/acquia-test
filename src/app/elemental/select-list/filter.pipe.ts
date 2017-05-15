import {Pipe, PipeTransform} from '@angular/core';

@Pipe({
  name: 'optionsFilter'
})
export class FilterPipe implements PipeTransform {

  /**
   * filter by name or full_name attribute if exist or the whole item value
   * @param value
   * @param args
   * @returns {any}
   */
  transform(value: any, args?: any): any {
    return value.filter(item => {
      return (args !== undefined) ?
      (item.name !== undefined && item.name.toLowerCase().indexOf(args.toLowerCase()) !== -1)
      || (item.name === undefined && item.toLowerCase().indexOf(args.toLowerCase()) !== -1) : item;
    });
  }
}
