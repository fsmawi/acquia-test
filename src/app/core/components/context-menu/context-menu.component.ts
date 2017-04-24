import {Component, HostListener, OnInit} from '@angular/core';

import {ContextMenuService} from '../../services/context-menu.service';
import {MenuItem} from '../../models/menu-item';

// Global Scope, Window
declare const window;

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
   * menu location
   * @type {Object}
   */
  menuLocation = {top: 0, left: 0};

  /**
   * Items menu
   * @type {MenuItem[]}
   */
  items: MenuItem[] = [];

  /**
   * Handle all events that should close the menu
   */
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
   * @param {any}
   */
  show(menu: any) {
    this.menuOpen = true;
    this.items = menu.items;
    this.setMenuLocation(menu.mouseLocation.x, menu.mouseLocation.y);
  }

  /**
   * Set menu location
   * @param mouseLocationX
   * @param mouseLocationY
   */
  setMenuLocation(mouseLocationX, mouseLocationY) {

    // by default take mouse position
    this.menuLocation.left = mouseLocationX;
    this.menuLocation.top = mouseLocationY;

    if (mouseLocationX + 200 >= window.innerWidth) {
      this.menuLocation.left -= 200;
    }

    if (mouseLocationY + (this.items.length * 32) >= window.innerHeight) {
      this.menuLocation.top -= this.items.length * 32;
    }
  }

  /**
   * Initiate Component
   */
  ngOnInit() {
  }
}
