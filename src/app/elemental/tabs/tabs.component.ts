import {Component, ContentChildren, QueryList, AfterContentInit, EventEmitter, Output} from '@angular/core';

import {TabComponent} from '../tab/tab.component';

@Component({
  selector: 'e-tabs',
  templateUrl: './tabs.component.html',
  styleUrls: ['./tabs.component.scss']
})
export class TabsComponent implements AfterContentInit {

  /**
   * List of tabs
   * @type {QueryList<TabComponent>}
   */
  @ContentChildren(TabComponent)
  tabs: QueryList<TabComponent>;

  /**
   * EventEmitter to emit selected tab
   */
  @Output()
  selected = new EventEmitter();

  /**
   * Builds the component
   */
  constructor() { }

  /**
   * After component build select the first tab
   */
  ngAfterContentInit() {
    this.selectTab(this.tabs.first);
  }

  /**
   * Select tab
   * @param tab
   */
  selectTab(tab: TabComponent) {
    // reset all tabs
    this.tabs.toArray().forEach(item => item.deactivate());

    if (tab) {
      // activate the selected tab
      tab.activate();
      this.selected.emit({selectedTab: tab});
    }
  }
}
