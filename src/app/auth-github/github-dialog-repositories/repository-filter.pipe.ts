import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'repositoryFilter'
})
export class RepositoryFilterPipe implements PipeTransform {

  /**
   * Filter repositories according to thier full name
   * @param  value
   * @param  args
   */
  transform(value: any, args?: any): any {
    return value.filter(repository => {
      return (args !== undefined) ? (repository.full_name.toLowerCase().indexOf(args.toLowerCase()) !== -1) : repository;
    });
  }
}
