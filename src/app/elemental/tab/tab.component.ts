import {Component, Input} from '@angular/core';

import {Tab} from './tab.interface';


@Component({
  selector: 'e-tab',
  templateUrl: './tab.component.html',
  styleUrls: ['./tab.component.scss']
})
export class TabComponent implements Tab {

  /**
   * Tab title
   * @type {string}
   */
  @Input()
  tabTitle: string;

  /**
   * Flag to show/hide tab
   * @type {Boolean}
   */
  @Input()
  active = false;

  /**
   * Builds the component
   */
  constructor() { }

  /**
   * Activate Tab
   */
  activate() {
    this.active = true;
  }

  /**
   * Deactivate Tab
   */
  deactivate() {
    this.active = false;
  }
}
