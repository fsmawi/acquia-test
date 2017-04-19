import {Injectable} from '@angular/core';

@Injectable()
export class ContextMenuService {

  /**
   * Show context menu
   * @param items
   */
  show: (items: any) => void;

  /**
   * Initiate the service
   */
  constructor() { }
}
