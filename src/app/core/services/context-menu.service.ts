import {Injectable} from '@angular/core';

import {MenuItem} from '../../core/models/menu-item';

@Injectable()
export class ContextMenuService {

  /**
   * Show context menu
   * @param appId
   * @param jobId
   */
  show: (items: MenuItem[]) => void;

  /**
   * Initiate the service
   */
  constructor() { }

}
