import {Component, HostListener, OnInit} from '@angular/core';

import {ContextMenuService} from '../../services/context-menu.service';
import {environment} from '../../../../environments/environment';
import {MenuItem} from '../../models/menu-item';

@Component({
  selector: 'app-context-menu',
  templateUrl: './context-menu.component.html',
  styleUrls: ['./context-menu.component.scss']
})
export class ContextMenuComponent implements OnInit {

  /**
   * Flag to show/hide menu
   * @type {Boolean}
   */
  menuOpen = false;

  /**
   * Items menu
   * @type {MenuItem[]}
   */
  items: MenuItem[];

  @HostListener('document:click')
  @HostListener('document:contextmenu')
  @HostListener('window:scroll')
  @HostListener('document:keydown', ['$event'])
  public clickedOutside(): void {
    if (this.menuOpen) {
      this.menuOpen = false;
    }
  }

  /**
   * Builds the component
   * @param contextMenuService
   */
  constructor(private contextMenuService: ContextMenuService) {
    this.contextMenuService.show = this.show.bind(this);
  }

   /**
   * Show context menu
   * @param {MenuItem[]}
   */
  show(items: MenuItem[]) {
    this.menuOpen = true;
    this.items = items;
  }

  /**
   * Initiate Component
   */
  ngOnInit() {
    this.items = [];
  }
}
